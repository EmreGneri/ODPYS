<?php

namespace App\Http\Requests;

use App\Models\CourseLevel;
use App\Models\ScheduleEntry;
use App\Models\TeacherCourseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Programda tek bir hucreyi (sube + gun + slot) elle duzenleme.
 * Sartname 2.6.2/5b - yonetici sihirbazin urettigi programa mudahale
 * edebilmeli.
 */
class ProgramDuzenleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'teacher_id' => 'required|exists:teachers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Ders seçmelisiniz.',
            'teacher_id.required' => 'Öğretmen seçmelisiniz.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sube = $this->route('sube');
            $weekday = (int) $this->route('weekday');
            $slot = (int) $this->route('slot');
            $dersId = $this->input('course_id');
            $ogretmenId = $this->input('teacher_id');

            // Ders bu subenin seviyesinde tanimli mi (mufredatta var mi)
            $dersUygun = CourseLevel::where('level_id', $sube->level_id)
                ->where('course_id', $dersId)
                ->exists();

            if (! $dersUygun) {
                $validator->errors()->add('course_id', 'Bu ders, şubenin seviyesinin müfredatında tanımlı değil.');

                return;
            }

            // Ogretmen bu dersi bu seviyede vermeye yetkili mi
            $yetkili = TeacherCourseLevel::where('teacher_id', $ogretmenId)
                ->where('course_id', $dersId)
                ->where('level_id', $sube->level_id)
                ->exists();

            if (! $yetkili) {
                $validator->errors()->add('teacher_id', 'Bu öğretmen, bu dersi bu seviyede vermeye yetkili değil.');

                return;
            }

            // Ogretmen bu gun/saatte BASKA bir subede zaten ders veriyor mu.
            // Kendi subesindeki mevcut kaydiyla cakismasin diye section_id
            // farkli olanlara bakiyorum.
            $cakisma = ScheduleEntry::where('teacher_id', $ogretmenId)
                ->where('weekday', $weekday)
                ->where('slot_no', $slot)
                ->where('section_id', '!=', $sube->id)
                ->exists();

            if ($cakisma) {
                $validator->errors()->add('teacher_id', 'Bu öğretmen bu saatte başka bir şubede ders veriyor.');
            }
        });
    }
}
