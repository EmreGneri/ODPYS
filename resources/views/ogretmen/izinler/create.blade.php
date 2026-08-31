<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni İzin Talebi</h2>
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

                <form method="POST" action="{{ route('ogretmen.izinler.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700">
                                Başlangıç tarih ve saati
                            </label>
                            <input type="datetime-local" id="starts_at" name="starts_at"
                                   value="{{ old('starts_at') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700">
                                Bitiş tarih ve saati
                            </label>
                            <input type="datetime-local" id="ends_at" name="ends_at"
                                   value="{{ old('ends_at') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700">
                            Gerekçe (opsiyonel)
                        </label>
                        <textarea id="reason" name="reason" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('reason') }}</textarea>
                    </div>

                    <p class="text-xs text-gray-500">
                        Talebiniz yönetici onayına sunulacak. Onaylanırsa bu tarih
                        aralığındaki dersleriniz programda izinli olarak görünür,
                        şablon programınız değişmez.
                    </p>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Talebi Gönder
                        </button>
                        <a href="{{ route('ogretmen.izinler.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
