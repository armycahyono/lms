<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Material;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignedFileController extends Controller
{
    public function material(Material $material): StreamedResponse
    {
        abort_unless($material->is_published && $material->file_path && Storage::disk('local')->exists($material->file_path), 404);

        return Storage::disk('local')->download($material->file_path);
    }

    public function assignment(Assignment $assignment): StreamedResponse
    {
        abort_unless($assignment->is_published && $assignment->attachment_path && Storage::disk('local')->exists($assignment->attachment_path), 404);

        return Storage::disk('local')->download($assignment->attachment_path);
    }
}
