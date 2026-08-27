<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Okul Ayarlarını Düzenle
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('okul.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Okul Adı" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name', $okul->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="city" value="İl" />
                            <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                                          :value="old('city', $okul->city)" required />
                            <x-input-error :messages="$errors->get('city')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="district" value="İlçe" />
                            <x-text-input id="district" name="district" type="text" class="mt-1 block w-full"
                                          :value="old('district', $okul->district)" required />
                            <x-input-error :messages="$errors->get('district')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="lessons_per_day" value="Günde Ders Sayısı" />
                            <x-text-input id="lessons_per_day" name="lessons_per_day" type="number" class="mt-1 block w-full"
                                          :value="old('lessons_per_day', $okul->lessons_per_day)" required />
                            <x-input-error :messages="$errors->get('lessons_per_day')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="lesson_duration" value="Ders Süresi (dk)" />
                            <x-text-input id="lesson_duration" name="lesson_duration" type="number" class="mt-1 block w-full"
                                          :value="old('lesson_duration', $okul->lesson_duration)" required />
                            <x-input-error :messages="$errors->get('lesson_duration')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="break_duration" value="Mola Süresi (dk)" />
                            <x-text-input id="break_duration" name="break_duration" type="number" class="mt-1 block w-full"
                                          :value="old('break_duration', $okul->break_duration)" required />
                            <x-input-error :messages="$errors->get('break_duration')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Eğitim günleri ve başlangıç saatleri --}}
                    <div class="border-t pt-6">
                        <h3 class="font-medium text-gray-900 mb-1">Eğitim Günleri ve Başlangıç Saatleri</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Eğitim verilen günleri işaretleyin ve her gün için ders başlangıç saatini belirtin.
                        </p>

                        <div class="space-y-2">
                            @foreach ($gunVerileri as $no => $g)
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 w-36">
                                        <input type="checkbox"
                                               name="days[{{ $no }}][is_active]"
                                               value="1"
                                               @checked(old("days.$no.is_active", $g['aktif']))
                                               class="rounded border-gray-300 text-gray-800 shadow-sm">
                                        <span class="text-sm">{{ $g['ad'] }}</span>
                                    </label>

                                    <input type="time"
                                           name="days[{{ $no }}][start_time]"
                                           value="{{ old("days.$no.start_time", $g['saat']) }}"
                                           class="rounded-md border-gray-300 shadow-sm text-sm">

                                    <x-input-error :messages="$errors->get("days.$no.start_time")" />
                                </div>
                            @endforeach
                        </div>

                        <x-input-error :messages="$errors->get('days')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 border-t pt-6">
                        <x-primary-button>Kaydet</x-primary-button>
                        <a href="{{ route('okul.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
