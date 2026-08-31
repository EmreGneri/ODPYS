<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yük Dağılımı Raporu</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('cizelge.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Ders Programı</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Toplam ders saati</div>
                        <div class="text-2xl font-semibold">{{ $toplamSaat }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">İş düşen öğretmen</div>
                        <div class="text-2xl font-semibold">{{ $calisanSayisi }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Boştaki öğretmen</div>
                        <div class="text-2xl font-semibold">{{ $bostaSayisi }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">En yüklü</div>
                        <div class="text-2xl font-semibold">{{ $enYuklu }} saat</div>
                    </div>
                    <div>
                        <div class="text-gray-500">En az yüklü</div>
                        <div class="text-2xl font-semibold">{{ $enAzYuklu }} saat</div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mt-4">
                    Fark <strong>{{ $enYuklu - $enAzYuklu }} saat</strong>.
                    Hakkaniyet brans icinde saglaniyor: bir branstaki ogretmen sayisi
                    o dersin toplam saatini esit bolemiyorsa fark kacinilmaz olur.
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="border-b text-left text-gray-500">
                            <th class="px-6 py-3 font-medium">Öğretmen</th>
                            <th class="px-6 py-3 font-medium">Branş</th>
                            <th class="px-6 py-3 font-medium">Yük</th>
                            <th class="px-6 py-3 font-medium w-48">Doluluk</th>
                            <th class="px-6 py-3 font-medium">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($satirlar as $s)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('cizelge.ogretmen', $s['id']) }}"
                                       class="text-indigo-600 hover:text-indigo-900">{{ $s['ad'] }}</a>
                                </td>
                                <td class="px-6 py-3 text-gray-600">
                                    @if (count($s['branslar']) > 0)
                                        {{ implode(', ', $s['branslar']) }}
                                    @else
                                        <span class="text-amber-600 text-xs">Ders atanmamis</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 tabular-nums">
                                    {{ $s['saat'] }} / {{ $s['kapasite'] }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="h-2 w-full rounded bg-gray-100 overflow-hidden">
                                        <div class="h-2 rounded {{ $s['oran'] >= 90 ? 'bg-red-400' : ($s['oran'] >= 60 ? 'bg-green-500' : 'bg-gray-400') }}"
                                             style="width: {{ min($s['oran'], 100) }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 tabular-nums">%{{ $s['oran'] }}</div>
                                </td>
                                <td class="px-6 py-3">
                                    @if (! $s['aktif'])
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">Pasif</span>
                                    @elseif ($s['saat'] === 0)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">Bosta</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Gorevli</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Ogretmen yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
