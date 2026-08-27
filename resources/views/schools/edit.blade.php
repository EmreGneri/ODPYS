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

                    <div class="flex items-center gap-4">
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
