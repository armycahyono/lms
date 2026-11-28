<?php

namespace App\Services;

use App\Models\QuestionBank;
use App\Models\Subject;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class QuestionBankWordService
{
    public function buildWordTemplate(): string
    {
        $tmpFile = sys_get_temp_dir().'/template_bank_soal_'.uniqid().'.docx';

        $zip = new ZipArchive;
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');

        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('word/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>
  <w:style w:type="table" w:default="1" w:styleId="TableNormal"><w:name w:val="Normal Table"/></w:style>
</w:styles>');

        $rows = [
            ['Bab / Kategori', 'Bab 1: Contoh Materi'],
            ['Kisi-Kisi', 'Siswa mampu membedakan kalimat fakta dan opini'],
            ['Kesulitan', 'sedang'],
            ['Poin', '10'],
            ['Pertanyaan', 'Tulis teks pertanyaan di sini (boleh beberapa paragraf, tebal/miring, atau paste gambar langsung di sel ini).'],
            ['Opsi A', 'Pilihan A (boleh teks atau paste gambar)'],
            ['Opsi B', 'Pilihan B'],
            ['Opsi C', 'Pilihan C'],
            ['Opsi D', 'Pilihan D'],
            ['Opsi E', 'Pilihan E (opsional)'],
            ['Pasangan Menjodohkan', 'Isi jika tipe Menjodohkan: Kiri1=Kanan1|Kiri2=Kanan2'],
            ['Kunci', 'B'],
        ];

        $trXml = '';
        foreach ($rows as [$label, $val]) {
            $l = htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $v = htmlspecialchars($val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $trXml .= "<w:tr>
                <w:tc><w:tcPr><w:tcW w:w=\"2800\" w:type=\"dxa\"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>{$l}</w:t></w:r></w:p></w:tc>
                <w:tc><w:tcPr><w:tcW w:w=\"6400\" w:type=\"dxa\"/></w:tcPr><w:p><w:r><w:t>{$v}</w:t></w:r></w:p></w:tc>
            </w:tr>";
        }

        $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p><w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>Template Impor Bank Soal LMS SMK PGRI Kras</w:t></w:r></w:p>
    <w:p><w:r><w:t>Petunjuk: Salin (copy-paste) tabel di bawah ini untuk setiap butir soal baru. Gambar dan format paragraf di dalam sel Pertanyaan atau Opsi akan otomatis terbaca oleh LMS.</w:t></w:r></w:p>
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="9200" w:type="dxa"/>
        <w:tblBorders>
          <w:top w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
          <w:left w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
          <w:bottom w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
          <w:right w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
          <w:insideH w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
          <w:insideV w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tblGrid>
        <w:gridCol w:w="2800"/>
        <w:gridCol w:w="6400"/>
      </w:tblGrid>
      '.$trXml.'
    </w:tbl>
    <w:p/>
    <w:sectPr>
      <w:pgSz w:w="11906" w:h="16838"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
    </w:sectPr>
  </w:body>
</w:document>';

        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();

        $binary = (string) file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     duplicates: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    public function parseUploadedDocx(string $filePath, array $context, int $teacherId): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return ['rows' => [], 'duplicates' => [], 'errors' => ['File Word (.docx) tidak dapat dibuka atau rusak.']];
        }

        $docContent = $zip->getFromName('word/document.xml');
        if ($docContent === false) {
            $zip->close();

            return ['rows' => [], 'duplicates' => [], 'errors' => ['Isi dokumen Word (word/document.xml) tidak ditemukan.']];
        }

        $relMap = [];
        $relsContent = $zip->getFromName('word/_rels/document.xml.rels');
        if ($relsContent !== false) {
            $relsDom = new DOMDocument;
            @$relsDom->loadXML($relsContent);
            foreach ($relsDom->getElementsByTagName('Relationship') as $rel) {
                $id = $rel->getAttribute('Id');
                $target = $rel->getAttribute('Target');
                if ($id !== '' && $target !== '') {
                    $relMap[$id] = str_starts_with($target, 'word/') ? $target : 'word/'.ltrim($target, '/');
                }
            }
        }

        $dom = new DOMDocument;
        @$dom->loadXML($docContent);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $existingQuestions = QuestionBank::where('teacher_id', $teacherId)
            ->pluck('question_text')
            ->map(fn ($t) => mb_strtolower(trim(strip_tags((string) $t))))
            ->flip()
            ->all();

        $subject = ! empty($context['subject_id']) ? Subject::find($context['subject_id']) : null;
        $type = (string) $context['type'];

        $rows = [];
        $duplicates = [];
        $errors = [];

        $tables = $xpath->query('//w:body/w:tbl');
        if (! $tables || $tables->length === 0) {
            $zip->close();

            return ['rows' => [], 'duplicates' => [], 'errors' => ['Tidak ditemukan tabel soal di dalam file Word (.docx). Gunakan template tabel yang disediakan.']];
        }

        foreach ($tables as $tIdx => $table) {
            $soalNum = $tIdx + 1;
            $fields = [];

            $trNodes = $xpath->query('./w:tr', $table);
            if (! $trNodes) {
                continue;
            }

            foreach ($trNodes as $tr) {
                $tcNodes = $xpath->query('./w:tc', $tr);
                if (! $tcNodes || $tcNodes->length < 2) {
                    continue;
                }

                $keyRaw = mb_strtolower(trim($this->extractPlainText($xpath, $tcNodes->item(0))));
                $valCell = $tcNodes->item(1);
                $fields[$keyRaw] = [
                    'text' => trim($this->extractPlainText($xpath, $valCell)),
                    'html' => trim($this->extractFormattedHtml($xpath, $valCell)),
                    'image' => $this->extractFirstImage($xpath, $valCell, $zip, $relMap),
                ];
            }

            $qField = $this->findField($fields, ['pertanyaan', 'soal']);
            $questionHtml = $qField['html'] ?? '';
            $questionPlain = $qField['text'] ?? '';
            $questionImage = $qField['image'] ?? null;

            if ($questionPlain === '' && $questionImage === null) {
                $errors[] = "Tabel Soal #{$soalNum}: Pertanyaan wajib diisi.";

                continue;
            }

            $normText = mb_strtolower($questionPlain !== '' ? $questionPlain : ('img_'.$soalNum));
            if ($questionPlain !== '' && isset($existingQuestions[$normText])) {
                $duplicates[] = "Tabel Soal #{$soalNum}: \"".Str::limit($questionPlain, 60).'" sudah ada di Bank Soal Anda (dilewati).';

                continue;
            }

            $category = ($this->findField($fields, ['bab', 'kategori', 'bab / kategori'])['text'] ?? '') ?: null;
            $kisi = ($this->findField($fields, ['kisi', 'kisi-kisi', 'indikator'])['text'] ?? '') ?: null;
            $diffRaw = mb_strtolower($this->findField($fields, ['kesulitan', 'tingkat kesulitan'])['text'] ?? 'sedang');
            $difficulty = in_array($diffRaw, ['mudah', 'sedang', 'sulit'], true) ? $diffRaw : 'sedang';
            $pointsRaw = $this->findField($fields, ['poin', 'skor'])['text'] ?? '10';
            $points = is_numeric($pointsRaw) && (float) $pointsRaw > 0 ? (float) $pointsRaw : 10.0;
            $answerRaw = trim($this->findField($fields, ['kunci', 'kunci jawaban'])['text'] ?? '');

            $options = null;
            $optionImages = null;
            $correctAnswer = $answerRaw !== '' ? $answerRaw : null;

            if (in_array($type, ['multiple_choice', 'multiple_choice_complex'], true)) {
                $opts = [];
                $optImgs = [];
                foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
                    $f = $this->findField($fields, ['opsi '.strtolower($letter), 'pilihan '.strtolower($letter)]);
                    if ($f && ($f['text'] !== '' || $f['image'] !== null)) {
                        $opts[$letter] = $f['text'] !== '' ? $f['text'] : "[Gambar {$letter}]";
                        if ($f['image'] !== null) {
                            $optImgs[$letter] = $f['image'];
                        }
                    }
                }

                if (count($opts) < 2) {
                    $errors[] = "Tabel Soal #{$soalNum}: Soal pilihan ganda minimal harus memiliki 2 opsi jawaban.";

                    continue;
                }

                if ($type === 'multiple_choice') {
                    $ansKey = strtoupper((string) $correctAnswer);
                    if (! array_key_exists($ansKey, $opts)) {
                        $errors[] = "Tabel Soal #{$soalNum}: Kunci \"{$answerRaw}\" tidak cocok dengan opsi tersedia.";

                        continue;
                    }
                    $correctAnswer = $ansKey;
                } else {
                    $keys = collect(explode(',', strtoupper((string) $correctAnswer)))
                        ->map(fn ($k) => trim($k))
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();

                    if ($keys->isEmpty() || $keys->contains(fn ($k) => ! array_key_exists($k, $opts))) {
                        $errors[] = "Tabel Soal #{$soalNum}: Kunci PG Kompleks harus dipisah koma (misal: A,C).";

                        continue;
                    }
                    $correctAnswer = $keys->join(',');
                }

                $options = $opts;
                $optionImages = $optImgs !== [] ? $optImgs : null;
            } elseif ($type === 'matching') {
                $matchingRaw = $this->findField($fields, ['pasangan menjodohkan', 'pasangan', 'menjodohkan'])['text'] ?? '';
                $pairs = [];
                foreach (explode('|', $matchingRaw) as $pairIdx => $pairStr) {
                    $parts = explode('=', $pairStr, 2);
                    if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                        $pairs[(string) ($pairIdx + 1)] = ['left' => trim($parts[0]), 'right' => trim($parts[1])];
                    }
                }
                if (count($pairs) < 2) {
                    $errors[] = "Tabel Soal #{$soalNum}: Soal Menjodohkan minimal memiliki 2 pasangan Kiri=Kanan dipisah |.";

                    continue;
                }
                $options = $pairs;
                $correctAnswer = json_encode(
                    collect($pairs)->mapWithKeys(fn ($p) => [$p['left'] => $p['right']])->all(),
                    JSON_UNESCAPED_UNICODE
                );
            } elseif ($type === 'short_answer' && ($correctAnswer === null || $correctAnswer === '')) {
                $errors[] = "Tabel Soal #{$soalNum}: Soal Isian Singkat wajib memiliki Kunci Jawaban.";

                continue;
            }

            $rows[] = [
                'subject_id' => $subject?->id,
                'subject_name' => $subject?->name ?? 'Umum',
                'grade_level' => ! empty($context['grade_level']) ? (int) $context['grade_level'] : null,
                'department' => ! empty($context['department']) ? (string) $context['department'] : null,
                'category' => $category,
                'kisi_indicator' => $kisi,
                'difficulty' => $difficulty,
                'type' => $type,
                'type_label' => QuestionBank::TYPES[$type] ?? $type,
                'question_text' => $questionHtml !== '' ? $questionHtml : $questionPlain,
                'question_image' => $questionImage,
                'options_json' => $options,
                'option_images_json' => $optionImages,
                'correct_answer' => $correctAnswer,
                'points' => $points,
                'is_shared' => (bool) ($context['is_shared'] ?? false),
            ];
            if ($questionPlain !== '') {
                $existingQuestions[$normText] = true;
            }
        }

        $zip->close();

        return [
            'rows' => $rows,
            'duplicates' => $duplicates,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, array{text: string, html: string, image: string|null}>  $fields
     * @param  array<int, string>  $aliases
     * @return array{text: string, html: string, image: string|null}|null
     */
    private function findField(array $fields, array $aliases): ?array
    {
        foreach ($aliases as $alias) {
            if (isset($fields[$alias])) {
                return $fields[$alias];
            }
        }
        foreach ($fields as $key => $val) {
            foreach ($aliases as $alias) {
                if (str_contains($key, $alias)) {
                    return $val;
                }
            }
        }

        return null;
    }

    private function extractPlainText(DOMXPath $xpath, ?DOMElement $cell): string
    {
        if (! $cell) {
            return '';
        }

        $paras = $xpath->query('.//w:p', $cell);
        $lines = [];
        if ($paras) {
            foreach ($paras as $p) {
                $txt = '';
                $tNodes = $xpath->query('.//w:t', $p);
                if ($tNodes) {
                    foreach ($tNodes as $t) {
                        $txt .= $t->textContent;
                    }
                }
                if (trim($txt) !== '') {
                    $lines[] = trim($txt);
                }
            }
        }

        return implode("\n", $lines);
    }

    private function extractFormattedHtml(DOMXPath $xpath, ?DOMElement $cell): string
    {
        if (! $cell) {
            return '';
        }

        $paras = $xpath->query('.//w:p', $cell);
        $htmlParts = [];
        if ($paras) {
            foreach ($paras as $p) {
                $runs = $xpath->query('./w:r', $p);
                $paraContent = '';
                if ($runs) {
                    foreach ($runs as $r) {
                        $tNodes = $xpath->query('./w:t', $r);
                        $runText = '';
                        if ($tNodes) {
                            foreach ($tNodes as $t) {
                                $runText .= $t->textContent;
                            }
                        }
                        if ($runText === '') {
                            continue;
                        }
                        $safe = htmlspecialchars($runText, ENT_QUOTES, 'UTF-8');
                        if ($xpath->query('./w:rPr/w:b', $r)->length > 0) {
                            $safe = "<strong>{$safe}</strong>";
                        }
                        if ($xpath->query('./w:rPr/w:i', $r)->length > 0) {
                            $safe = "<em>{$safe}</em>";
                        }
                        if ($xpath->query('./w:rPr/w:u', $r)->length > 0) {
                            $safe = "<u>{$safe}</u>";
                        }
                        $paraContent .= $safe;
                    }
                }
                if (trim(strip_tags($paraContent)) !== '') {
                    $htmlParts[] = "<p>{$paraContent}</p>";
                }
            }
        }

        return implode('', $htmlParts);
    }

    /**
     * @param  array<string, string>  $relMap
     */
    private function extractFirstImage(DOMXPath $xpath, ?DOMElement $cell, ZipArchive $zip, array $relMap): ?string
    {
        if (! $cell) {
            return null;
        }

        $blips = $xpath->query('.//a:blip', $cell);
        if (! $blips || $blips->length === 0) {
            return null;
        }

        $blip = $blips->item(0);
        if (! $blip instanceof DOMElement) {
            return null;
        }

        $rId = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
        if ($rId === '' || ! isset($relMap[$rId])) {
            return null;
        }

        $zipPath = $relMap[$rId];
        $binary = $zip->getFromName($zipPath);
        if ($binary === false) {
            return null;
        }

        $ext = pathinfo($zipPath, PATHINFO_EXTENSION) ?: 'png';
        $storedPath = 'question-media/imported/'.uniqid('docx_', true).'.'.$ext;
        Storage::disk('local')->put($storedPath, $binary);

        return $storedPath;
    }
}
