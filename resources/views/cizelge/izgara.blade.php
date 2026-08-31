<x-app-layout>
    <x-slot name="header">
        <div class="flex items-baseline gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $baslik }}</h2>
            <span class="text-sm text-gray-500">{{ $altBaslik }}</span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Üst satır: geri dön + toplam --}}
            <div class="flex items-center justify-between">
                <a href="{{ $geriUrl }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Ders Programı
                </a>
                <span class="text-sm text-gray-500">Toplam {{ $toplamSaat }} ders saati</span>
            </div>

            {{-- Gezinme şeridi --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-3">

                {{-- Önceki / Sonraki --}}
                <div class="flex items-center justify-between gap-4">
                    @if ($gezinme['onceki'])
                        <a href="{{ $gezinme['onceki']['url'] }}"
                           class="inline-flex items-center gap-1 text-sm text-gray-700 hover:text-gray-900">
                            &larr; {{ $gezinme['onceki']['ad'] }}
                        </a>
                    @else
                        <span class="text-sm text-gray-300">&larr; ilk kayıt</span>
                    @endif

                    <span class="text-xs uppercase tracking-wide text-gray-400">
                        {{ $gezinme['baslik'] }}
                    </span>

                    @if ($gezinme['sonraki'])
                        <a href="{{ $gezinme['sonraki']['url'] }}"
                           class="inline-flex items-center gap-1 text-sm text-gray-700 hover:text-gray-900">
                            {{ $gezinme['sonraki']['ad'] }} &rarr;
                        </a>
                    @else
                        <span class="text-sm text-gray-300">son kayıt &rarr;</span>
                    @endif
                </div>

                {{-- Bütün kayıtlar, yan yana. Çok sayıda olursa yatay kayar. --}}
                <div class="flex flex-wrap gap-2 border-t pt-3">
                    @foreach ($gezinme['ogeler'] as $oge)
                        <a href="{{ $oge['url'] }}"
                           class="px-3 py-1.5 rounded text-sm border whitespace-nowrap
                                  {{ $oge['aktif']
                                     ? 'bg-gray-800 text-white border-gray-800'
                                     : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                            {{ $oge['ad'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Çizelge --}}
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
                                                <div class="text-gray-300 text-xs px-2 py-1.5">boş</div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-500">
                    Saatler okul ayarlarından hesaplanıyor; veritabanında slot numarası saklanıyor.
                    Ders süresi değişince bütün çizelge kendiliğinden kayar.
                    Her günün başlangıç saati farklı olabilir.
                </p>
            @endif

        </div>
    </div>
</x-app-layout>
