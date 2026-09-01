<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $sube->name }} · {{ $gunAdi }} · {{ $slot }}. ders
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('cizelge.sube', $sube) }}" class="text-sm text-gray-600 hover:text-gray-900">
                &larr; {{ $sube->name }} Çizelgesi
            </a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-6 rounded bg-red-50 border border-red-200 p-4">
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $hata)
                                <li>{{ $hata }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('program.hucre.guncelle', [$sube, $weekday, $slot]) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700">Ders</label>
                        <select id="course_id" name="course_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">— Seçin —</option>
                            @foreach ($dersler as $ders)
                                <option value="{{ $ders->id }}"
                                    {{ old('course_id', $mevcut->course_id ?? null) == $ders->id ? 'selected' : '' }}>
                                    {{ $ders->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700">Öğretmen</label>
                        <select id="teacher_id" name="teacher_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">— Seçin —</option>
                            @foreach ($ogretmenler as $ogretmen)
                                <option value="{{ $ogretmen->id }}"
                                    {{ old('teacher_id', $mevcut->teacher_id ?? null) == $ogretmen->id ? 'selected' : '' }}>
                                    {{ $ogretmen->first_name }} {{ $ogretmen->last_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Sadece bu şubenin seviyesinde ders vermeye yetkili öğretmenler listelenir.
                        </p>
                    </div>

                    <div class="flex items-center gap-4 border-t pt-6">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Kaydet
                        </button>
                        <a href="{{ route('cizelge.sube', $sube) }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

                @if ($mevcut)
                    <form method="POST" action="{{ route('program.hucre.bosalt', [$sube, $weekday, $slot]) }}"
                          class="border-t pt-6 mt-6"
                          onsubmit="return confirm('Bu ders saatini boşaltmak istediğinize emin misiniz?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                            Bu ders saatini boşalt
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
