<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Models\Level;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\SectionCourseTeacher;

class SectionController extends Controller
{
    public function index()
    {
        // with()  -> ilgili veriyi toplu cekiyor, N+1 onleniyor (3 sorgu)
        // join()  -> levels.grade_number'a gore siralayabilmek icin
        // select()-> join sonrasi id/name sutunlari cakisiyor; olmasaydi
        //            sube adi yerine seviye adi gorunur, Duzenle
        //            baglantilari yanlis kayda giderdi
        $subeler = Section::with(['level', 'room'])
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->orderBy('levels.grade_number')
            ->orderBy('sections.name')
            ->select('sections.*')
            ->get();

        return view('sections.index', ['subeler' => $subeler]);
    }

    public function create()
    {
        return view('sections.create', [
            'seviyeler' => Level::orderBy('grade_number')->get(),
            'derslikler' => Room::whereDoesntHave('section')->orderBy('name')->get(),
        ]);
    }

    public function store(SectionRequest $request)
    {
        Section::create($request->validated());

        return redirect()
            ->route('subeler.index')
            ->with('success', 'Şube eklendi.');
    }

    public function edit(Section $sube)
    {
        return view('sections.edit', [
            'sube' => $sube,
            'seviyeler' => Level::orderBy('grade_number')->get(),

            // Bos derslikler ARTI bu subenin mevcut dersligi.
            // Ikinci kosul olmasaydi sube kendi dersligini listede
            // goremezdi. Dis sarmalayici sart: orWhere kendi
            // parantezinde kalsin diye.
            'derslikler' => Room::where(function ($sorgu) use ($sube) {
                $sorgu->whereDoesntHave('section')->orWhere('id', $sube->room_id);
            })->orderBy('name')->get(),
        ]);
    }

    public function update(SectionRequest $request, Section $sube)
    {
        $sube->update($request->validated());

        return redirect()
            ->route('subeler.index')
            ->with('success', 'Şube güncellendi.');
    }

    public function destroy(Section $sube)
    {
        // Uretilmis program korunuyor: schedule_entries ve
        // section_course_teacher CASCADE ile tanimli, sube silinince
        // o subenin butun cizelgesi sessizce ucardi.
        $programdaVar = ScheduleEntry::where('section_id', $sube->id)->exists()
            || SectionCourseTeacher::where('section_id', $sube->id)->exists();

        if ($programdaVar) {
            return redirect()
                ->route('subeler.index')
                ->with('error', "{$sube->name} silinemez: üretilmiş programda kullanılıyor. Önce programı sıfırlayın.");
        }

        $sube->delete();

        return redirect()
            ->route('subeler.index')
            ->with('success', 'Şube silindi.');
    }
}
