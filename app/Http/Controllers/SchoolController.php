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
}
