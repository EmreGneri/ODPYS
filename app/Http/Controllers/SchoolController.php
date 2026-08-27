<?php

namespace App\Http\Controllers;
use App\Models\School;
use Illuminate\Http\Request;


class SchoolController extends Controller
{
    public function index()
    {
        $okul = School::with('days')->first();
        $gunAdlari = [
            1 => 'Pazartesi',
            2 => 'Salı',
            3 => 'Çarşamba',
            4 => 'Perşembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            7 => 'Pazar',
        ];


        return view('schools.index', [
            'okul' => $okul,
            'gunAdlari' => $gunAdlari,
        ]);
    }
        public function edit()
    {
        $okul = School::first();

        return view('schools.edit', ['okul' => $okul]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'lessons_per_day' => 'required|integer|min:1|max:20',
            'lesson_duration' => 'required|integer|min:5|max:120',
            'break_duration' => 'required|integer|min:0|max:60',
        ]);

        School::first()->update($validated);

        return redirect()
            ->route('okul.index')
            ->with('success', 'Okul ayarları güncellendi.');
    }
}
