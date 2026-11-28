<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicYearSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isGuru() || $user->isAdmin(), 403);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $year = AcademicYear::findOrFail($validated['academic_year_id']);
        $request->session()->put('selected_academic_year_id', $year->id);

        return back()->with('status', "Menampilkan data Tahun Ajaran {$year->name}.");
    }
}
