<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ders Programı</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($programSayisi === 0)
                <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded">
                    Henüz program üretilmemiş.
                    <a href="{{ route('program.index') }}" class="underline">Program Oluşturma</a>
                    ekranından iki adımı çalıştırın.
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Yerleştirilmiş ders saati</div>
                            <div class="text-2xl font-semibold">{{ $programSayisi }}</div>
                        </div>

                        <a href="{{ route('cizelge.yuk') }}"
                           class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Yük Dağılımı Raporu
                        </a>
                    </div>
                </div>

                {{-- Şartname 5.3: zaman çakışması uyarıları --}}
                @php
                    $cakismaVar = count($cakismalar['sube']) + count($cakismalar['ogretmen']) + count($cakismalar['derslik']);
                @endphp

                @if ($cakismaVar === 0)
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">
                        <strong>Çakışma kontrolü temiz.</strong>
                        Hiçbir şube, öğretmen veya derslik aynı slotta iki kez görünmüyor.
                        Bu kontrol veritabanındaki üç benzersizlik kısıtının çalıştığını doğruluyor.
                    </div>
                @else
                    <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                        <strong>{{ $cakismaVar }} çakışma bulundu.</strong>
                        Şube: {{ count($cakismalar['sube']) }},
                        öğretmen: {{ count($cakismalar['ogretmen']) }},
                        derslik: {{ count($cakismalar['derslik']) }}.
                    </div>
                @endif
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Şube bazlı çizelge --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-1">Sınıf Çizelgeleri</h3>
                    <p class="text-sm text-gray-500 mb-4">Şartname 5.1 — her sınıf için haftalık çizelge</p>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($subeler as $sube)
                            <a href="{{ route('cizelge.sube', $sube) }}"
                               class="px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50">
                                {{ $sube->name }}
                            </a>
                        @empty
                            <span class="text-sm text-gray-500">Şube tanımlanmamış.</span>
                        @endforelse
                    </div>
                </div>

                {{-- Öğretmen bazlı program --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-1">Öğretmen Programları</h3>
                    <p class="text-sm text-gray-500 mb-4">Şartname 5.2 — öğretmen bazlı ders programı</p>

                    <div class="max-h-64 overflow-y-auto flex flex-wrap gap-2">
                        @forelse ($ogretmenler as $ogretmen)
                            <a href="{{ route('cizelge.ogretmen', $ogretmen) }}"
                               class="px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50">
                                {{ $ogretmen->first_name }} {{ $ogretmen->last_name }}
                            </a>
                        @empty
                            <span class="text-sm text-gray-500">Aktif öğretmen yok.</span>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
