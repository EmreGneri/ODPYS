<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Okul Ekle</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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

                <p class="text-sm text-gray-600 mb-6">
                    Okul eklendikten sonra otomatik olarak aktif okul yapılır ve
                    eğitim günlerini tanımlayacağınız ekrana yönlendirilirsiniz.
                    Seviye, şube, ders ve öğretmenleri daha sonra bu okul aktifken
                    ekleyebilirsiniz.
                </p>

                <form method="POST" action="{{ route('okullar.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Okul Adı</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">İl</label>
                            <input id="city" name="city" type="text" value="{{ old('city') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="district" class="block text-sm font-medium text-gray-700">İlçe</label>
                            <input id="district" name="district" type="text" value="{{ old('district') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="lessons_per_day" class="block text-sm font-medium text-gray-700">
                                Günde Ders Sayısı
                            </label>
                            <input id="lessons_per_day" name="lessons_per_day" type="number"
                                   value="{{ old('lessons_per_day', 7) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="lesson_duration" class="block text-sm font-medium text-gray-700">
                                Ders Süresi (dk)
                            </label>
                            <input id="lesson_duration" name="lesson_duration" type="number"
                                   value="{{ old('lesson_duration', 40) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="break_duration" class="block text-sm font-medium text-gray-700">
                                Mola Süresi (dk)
                            </label>
                            <input id="break_duration" name="break_duration" type="number"
                                   value="{{ old('break_duration', 10) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t pt-6">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Okulu Ekle
                        </button>
                        <a href="{{ route('okullar.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
