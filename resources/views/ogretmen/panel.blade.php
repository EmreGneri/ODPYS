<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Merhaba, {{ $ogretmen->first_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Ad Soyad</div>
                        <div class="font-medium">{{ $ogretmen->full_name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">E-posta</div>
                        <div class="font-medium">{{ $ogretmen->email }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Branş</div>
                        <div class="font-medium">
                            {{ count($branslar) > 0 ? implode(', ', $branslar) : 'Atanmamış' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Haftalık Yük</div>
                        <div class="font-medium tabular-nums">
                            {{ $toplamSaat }} / {{ $ogretmen->max_weekly_hours }} saat
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center justify-between flex-wrap gap-4">
                <div class="text-sm text-gray-600">
                    @if ($bekleyenIzinSayisi > 0)
                        <span class="text-amber-700 font-medium">{{ $bekleyenIzinSayisi }} bekleyen izin talebiniz var.</span>
                    @else
                        Bekleyen izin talebiniz yok.
                    @endif
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('ogretmen.izinler.index') }}"
                       class="text-sm text-indigo-600 hover:text-indigo-900">İzin Taleplerim</a>
                    <a href="{{ route('ogretmen.izinler.create') }}"
                       class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Yeni İzin Talebi
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">Haftalık Ders Programım</h3>

                @if (empty($gunler))
                    <p class="text-sm text-gray-500">Program henüz üretilmemiş.</p>
                @else
                    <div class="overflow-x-auto">
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
                                                    <div class="rounded border px-2 py-1.5
                                                        {{ ($hucre['izinli'] ?? false)
                                                            ? 'border-amber-300 bg-amber-50'
                                                            : 'border-gray-200 bg-gray-50' }}">
                                                        @if ($hucre['izinli'] ?? false)
                                                            <div class="text-xs font-medium text-amber-800">İzinli</div>
                                                            <div class="text-xs text-amber-700">
                                                                Normalde: {{ $hucre['ust'] }} · {{ $hucre['alt'] }}
                                                            </div>
                                                            @if ($hucre['izinSebep'])
                                                                <div class="text-xs text-amber-600 italic">{{ $hucre['izinSebep'] }}</div>
                                                            @endif
                                                        @else
                                                            <div class="font-medium text-gray-800">{{ $hucre['ust'] }}</div>
                                                            <div class="text-xs text-gray-500">{{ $hucre['alt'] }}</div>
                                                        @endif
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

                    <p class="text-xs text-gray-500 mt-3">
                        Bu haftanın onaylı izinleri, ilgili ders saatlerinde "İzinli" olarak
                        işaretlenir. Tüm izin geçmişiniz "İzin Taleplerim" ekranında.
                    </p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
