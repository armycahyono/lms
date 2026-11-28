<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ProjectModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectModuleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeYear = AcademicYear::selectedFor($user);
        $yearId = $activeYear?->id;

        $projects = ProjectModule::query()
            ->forAcademicYear($yearId)
            ->when($user->isSiswa(), fn ($q) => $q->where('status', 'active'))
            ->with('coordinator')
            ->latest('id')
            ->get();

        return view('projects.index', [
            'projects' => $projects,
            'activeYear' => $activeYear,
            'canManage' => $user->isGuru() || $user->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'theme' => ['required', 'string', 'max:150'],
            'phase' => ['nullable', 'string', 'max:30'],
            'dimensions' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'assessment_notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
        ]);

        $year = AcademicYear::selectedFor($user) ?? AcademicYear::active();
        abort_unless($year !== null, 422, 'Tahun ajaran aktif belum tersedia.');

        $path = $request->hasFile('attachment')
            ? $request->file('attachment')->store('project-modules', 'local')
            : null;

        ProjectModule::create([
            'academic_year_id' => $year->id,
            'coordinator_id' => $user->id,
            'title' => $validated['title'],
            'theme' => $validated['theme'],
            'phase' => $validated['phase'] ?? null,
            'dimensions' => $validated['dimensions'] ?? null,
            'objectives' => $validated['objectives'] ?? null,
            'activities' => $validated['activities'] ?? null,
            'assessment_notes' => $validated['assessment_notes'] ?? null,
            'attachment_path' => $path,
            'status' => 'active',
        ]);

        return back()->with('status', 'Modul Projek (P5) berhasil ditambahkan.');
    }

    public function download(ProjectModule $projectModule): StreamedResponse
    {
        abort_unless($projectModule->attachment_path && Storage::disk('local')->exists($projectModule->attachment_path), 404);

        return Storage::disk('local')->download($projectModule->attachment_path);
    }

    public function destroy(Request $request, ProjectModule $projectModule): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $projectModule->coordinator_id === $user->id, 403);

        if ($projectModule->attachment_path && Storage::disk('local')->exists($projectModule->attachment_path)) {
            Storage::disk('local')->delete($projectModule->attachment_path);
        }

        $projectModule->delete();

        return back()->with('status', 'Modul Projek (P5) berhasil dihapus.');
    }
}
