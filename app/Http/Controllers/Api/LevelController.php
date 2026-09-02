<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LevelRequest;
use App\Models\Level;
use App\Models\School;

class LevelController extends Controller
{
    public function index()
    {
        return Level::withCount('sections')->orderBy('grade_number')->get();
    }

    public function store(LevelRequest $request)
    {
        $validated = $request->validated();

        $seviye = School::aktif()->levels()->create([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return response()->json($seviye, 201);
    }

    public function update(LevelRequest $request, Level $seviye)
    {
        $validated = $request->validated();

        $seviye->update([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return $seviye;
    }

    public function destroy(Level $seviye)
    {
        $subeSayisi = $seviye->sections()->count();

        if ($subeSayisi > 0) {
            return response()->json([
                'message' => "{$seviye->name} silinemez: bu seviyeye bağlı {$subeSayisi} şube var. Önce şubeleri silin.",
            ], 422);
        }

        $seviye->delete();

        return response()->json(null, 204);
    }
}
