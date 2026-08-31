<x-app-layout>
    <x-slot name="header">
        <div class="flex items-baseline gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $baslik }}</h2>
            <span class="text-sm text-gray-500">{{ $altBaslik }}</span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="flex items-center justify-between">
                <a href="{{ $geriUrl }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Ders Programı</a>
                <span class="text-sm text-gray-500">Toplam {{ $toplamSaat }} ders saati</span>
            </div>

            @if (empty($gunler))
                <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded">
                    Aktif eğitim günü tanımlı değil.
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-3 py-3 text-left font-medium text-gray-500 w-28">Saat</th>
                                @foreach ($gunler as $gunNo => $gunAdi)
                                    <th class="px-3 py-3 text-left font-medium text-gray-500">{{ $gunAdi }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($slotlar as $slot)
                                <tr>
                                    <td class="px-3 py-2 align-top">
                                        <div class="font-medium text-gray-700">{{ $slot }}. ders</div>
                                    </td>

                                    @foreach ($gunler as $gunNo => $gunAdi)
                                        @php $hucre = $izgara[$gunNo][$slot] ?? null; @endphp

                                        <td class="px-3 py-2 align-top">
                                            @if ($hucre)
                                                <div class="rounded border border-gray-200 bg-gray-50 px-2 py-1.5">
                                                    <div class="font-medium text-gray-800">{{ $hucre['ust'] }}</div>
                                                    <div class="text-xs text-gray-500">{{ $hucre['alt'] }}</div>
                                                    <div class="text-xs text-gray-400 tabular-nums mt-0.5">
                                                        {{ $saatler[$gunNo][$slot]['baslangic'] ?? '' }}-{{ $saatler[$gunNo][$slot]['bitis'] ?? '' }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-gray-300 text-xs px-2 py-1.5">bos</div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-500">
                    Saatler okul ayarlarindan hesaplaniyor; veritabaninda slot numarasi saklaniyor.
                    Ders suresi degisince butun cizelge kendiliginden kayar.
                    Her gunun baslangic saati farkli olabilir.
                </p>
            @endif

        </div>
    </div>
</x-app-layout>
