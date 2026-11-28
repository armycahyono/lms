<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;

class BackupController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $backups = collect(File::files($backupDir))
            ->filter(fn ($file) => $this->isBackupFile($file->getFilename()))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'type' => str_ends_with($file->getFilename(), '.zip') ? 'Storage' : 'Database',
                'created_at' => date('d M Y H:i:s', $file->getMTime()),
                'mtime' => $file->getMTime(),
            ])
            ->sortByDesc('mtime')
            ->values();

        return view('admin.backups.index', compact('backups'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $options = [
            '--keep-days' => 7,
            '--keep-weekly' => 4,
            '--keep-monthly' => 6,
        ];

        if ($request->boolean('with_storage')) {
            $options['--with-storage'] = true;
        }

        $exitCode = Artisan::call('app:backup', $options);

        if ($exitCode !== 0) {
            return back()->with('error', 'Backup gagal dijalankan: '.trim(Artisan::output()));
        }

        return back()->with('success', 'Backup data berhasil dibuat.');
    }

    public function download(Request $request, string $filename): BinaryFileResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $path = $this->resolveSafeBackupPath($filename);

        return response()->download($path);
    }

    public function restore(Request $request, string $filename): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $path = $this->resolveSafeBackupPath($filename);

        if (str_ends_with($filename, '.zip')) {
            return back()->with('error', 'Pemulihan otomatis hanya mendukung file backup database (.sql.gz / .sqlite.gz).');
        }

        try {
            $this->restoreDatabaseFromFile($path, $filename);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'admin.restore_backup',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return back()->with('success', "Database berhasil dipulihkan dari {$filename}.");
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memulihkan database: '.$e->getMessage());
        }
    }

    public function restoreUpload(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'],
        ]);

        $uploadedFile = $validated['backup_file'];
        $originalName = $uploadedFile->getClientOriginalName();

        if (! str_ends_with($originalName, '.sql') && ! str_ends_with($originalName, '.sql.gz') && ! str_ends_with($originalName, '.sqlite.gz')) {
            return back()->with('error', 'Format file tidak didukung. Gunakan file .sql, .sql.gz, atau .sqlite.gz.');
        }

        $tempDir = storage_path('app/backups');
        File::ensureDirectoryExists($tempDir);
        $tempPath = $tempDir.'/upload_restore_'.uniqid('', true).'_'.basename($originalName);

        try {
            $uploadedFile->move($tempDir, basename($tempPath));
            $this->restoreDatabaseFromFile($tempPath, $originalName);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'admin.restore_backup_upload',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return back()->with('success', "Database berhasil dipulihkan dari file unggahan ({$originalName}).");
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memulihkan database: '.$e->getMessage());
        } finally {
            File::delete($tempPath);
        }
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $path = $this->resolveSafeBackupPath($filename);
        File::delete($path);

        return back()->with('success', "File backup {$filename} berhasil dihapus.");
    }

    protected function resolveSafeBackupPath(string $filename): string
    {
        $safeName = basename($filename);
        abort_unless($safeName === $filename && $this->isBackupFile($safeName), 404);

        $path = storage_path("app/backups/{$safeName}");
        abort_unless(File::exists($path), 404);

        return $path;
    }

    protected function isBackupFile(string $filename): bool
    {
        return str_ends_with($filename, '.sql.gz')
            || str_ends_with($filename, '.sqlite.gz')
            || str_ends_with($filename, '.zip');
    }

    protected function restoreDatabaseFromFile(string $path, string $originalName): void
    {
        $connectionName = (string) config('database.default');
        $connection = config("database.connections.{$connectionName}", []);
        $driver = (string) ($connection['driver'] ?? '');

        if ($driver === 'sqlite') {
            $dbPath = (string) ($connection['database'] ?? '');
            if ($dbPath === '' || $dbPath === ':memory:') {
                throw new RuntimeException('Database SQLite in-memory tidak mendukung pemulihan file.');
            }

            $content = str_ends_with($originalName, '.gz')
                ? $this->readGzipContent($path)
                : (string) File::get($path);

            File::put($dbPath, $content);
            Artisan::call('migrate', ['--force' => true]);

            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Driver database '{$driver}' belum didukung untuk pemulihan otomatis.");
        }

        $mysqlBinary = $this->resolveMysqlBinary();
        if (! $mysqlBinary) {
            throw new RuntimeException('Binary mysql client tidak ditemukan di PATH maupun Laragon.');
        }

        $tempSqlPath = null;
        $sqlPath = $path;

        if (str_ends_with($originalName, '.gz')) {
            $tempSqlPath = storage_path('app/backups/restore_tmp_'.uniqid('', true).'.sql');
            $this->decompressGzipToFile($path, $tempSqlPath);
            $sqlPath = $tempSqlPath;
        }

        try {
            $handle = fopen($sqlPath, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Gagal membaca file SQL untuk pemulihan.');
            }

            $command = [
                $mysqlBinary,
                '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? '3306'),
                '--user='.(string) ($connection['username'] ?? 'root'),
                '--default-character-set=utf8mb4',
                (string) ($connection['database'] ?? ''),
            ];

            $process = new Process(
                $command,
                base_path(),
                ['MYSQL_PWD' => (string) ($connection['password'] ?? '')],
                $handle,
                300
            );
            $process->run();
            fclose($handle);

            if (! $process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Proses mysql import gagal.');
            }

            Artisan::call('migrate', ['--force' => true]);
        } finally {
            if ($tempSqlPath !== null) {
                File::delete($tempSqlPath);
            }
        }
    }

    protected function decompressGzipToFile(string $gzPath, string $destPath): void
    {
        $gz = gzopen($gzPath, 'rb');
        if ($gz === false) {
            throw new RuntimeException('Gagal membuka arsip .gz.');
        }

        $out = fopen($destPath, 'wb');
        if ($out === false) {
            gzclose($gz);
            throw new RuntimeException('Gagal membuat file SQL sementara.');
        }

        while (! gzeof($gz)) {
            $chunk = gzread($gz, 65536);
            if ($chunk === false) {
                break;
            }
            fwrite($out, $chunk);
        }

        gzclose($gz);
        fclose($out);
    }

    protected function readGzipContent(string $gzPath): string
    {
        $gz = gzopen($gzPath, 'rb');
        if ($gz === false) {
            throw new RuntimeException('Gagal membuka arsip .gz.');
        }

        $content = '';
        while (! gzeof($gz)) {
            $chunk = gzread($gz, 65536);
            if ($chunk === false) {
                break;
            }
            $content .= $chunk;
        }
        gzclose($gz);

        return $content;
    }

    protected function resolveMysqlBinary(): ?string
    {
        $candidates = [
            'mysql',
            'mysql.exe',
        ];

        $laragonMysqlDirs = glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysql.exe') ?: [];
        foreach ($laragonMysqlDirs as $bin) {
            $candidates[] = $bin;
        }

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '\\') || str_contains($candidate, '/')) {
                if (File::exists($candidate)) {
                    return $candidate;
                }

                continue;
            }

            $check = new Process(['where', $candidate]);
            $check->run();
            if ($check->isSuccessful() && trim($check->getOutput()) !== '') {
                return $candidate;
            }

            $which = new Process(['which', $candidate]);
            $which->run();
            if ($which->isSuccessful() && trim($which->getOutput()) !== '') {
                return trim($which->getOutput());
            }
        }

        return null;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 2).' MB';
    }
}
