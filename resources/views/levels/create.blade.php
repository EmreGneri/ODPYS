<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Yeni Seviye Ekle
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('seviyeler.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="grade_number" value="Sınıf Numarası (9-12)" />
                        <x-text-input id="grade_number" name="grade_number" type="number"
                                      class="mt-1 block w-full"
                                      :value="old('grade_number')" required />
                        <x-input-error :messages="$errors->get('grade_number')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Kaydet</x-primary-button>
                        <a href="{{ route('seviyeler.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
