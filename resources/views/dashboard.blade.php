<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $okul?->name ?? 'ODPYS' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Özet kartlar --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                <a href="{{ route('seviyeler.index') }}"
                   class="block bg-white rounded-lg shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="text-3xl font-semibold text-gray-900">{{ $seviyeSayisi }}</div>
                    <div class="mt-1 text-sm text-gray-500">Seviye</div>
                </a>

                <a href="{{ route('subeler.index') }}"
                   class="block bg-white rounded-lg shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="text-3xl font-semibold text-gray-900">{{ $subeSayisi }}</div>
                    <div class="mt-1 text-sm text-gray-500">Şube</div>
                </a>

                <a href="{{ route('derslikler.index') }}"
                   class="block bg-white rounded-lg shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="text-3xl font-semibold text-gray-900">{{ $derslikSayisi }}</div>
                    <div class="mt-1 text-sm text-gray-500">Derslik</div>
                </a>

                <a href="{{ route('dersler.index') }}"
                   class="block bg-white rounded-lg shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="text-3xl font-semibold text-gray-900">{{ $dersSayisi }}</div>
                    <div class="mt-1 text-sm text-gray-500">Ders</div>
                </a>

                <div class="block bg-white rounded-lg shadow-sm p-5">
                    <div class="text-3xl font-semibold text-gray-900">{{ $ogretmenSayisi }}</div>
                    <div class="mt-1 text-sm text-gray-500">Aktif Öğretmen</div>
                </div>

            </div>

            {{-- Ders programı durumu --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Haftalık Ders Programı</h3>

                @if ($programKayitSayisi > 0)
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Oluşturuldu
                        </span>
                        <span class="text-sm text-gray-600">
                            {{ $programKayitSayisi }} ders saati yerleştirildi.
                        </span>
                    </div>
                @else
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            Henüz oluşturulmadı
                        </span>
                        <span class="text-sm text-gray-600">
                            Tüm tanımlamalar tamamlandıktan sonra program oluşturulabilir.
                        </span>
                    </div>
                @endif
            </div>

            {{-- Okul ayarları özeti --}}
            @if ($okul)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Okul Ayarları</h3>
                        <a href="{{ route('okul.index') }}" class="text-sm text-blue-600 hover:underline">
                            Detaylar
                        </a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Konum</div>
                            <div class="text-gray-900">{{ $okul->city }} / {{ $okul->district }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Günde ders</div>
                            <div class="text-gray-900">{{ $okul->lessons_per_day }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Ders süresi</div>
                            <div class="text-gray-900">{{ $okul->lesson_duration }} dk</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Mola süresi</div>
                            <div class="text-gray-900">{{ $okul->break_duration }} dk</div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
