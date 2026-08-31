<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Aylık Ücretler</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('cizelge.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Ders Programı</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Görevli öğretmen</div>
                        <div class="text-2xl font-semibold">{{ $gorevliSayisi }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Toplam aylık ödeme</div>
                        <div class="text-2xl font-semibold">{{ number_format($toplamAylikTutar, 2, ',', '.') }} TL</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Ortalama (öğretmen başı)</div>
                        <div class="text-2xl font-semibold">{{ number_format($ortalamaAylikTutar, 2, ',', '.') }} TL</div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mt-4">
                    Aylık tutar, haftalık ders programındaki saatlerin {{ $aylikHafta }} hafta ile
                    çarpılmasından hesaplanır (haftalık saat × dersin saatlik ücreti × {{ $aylikHafta }}).
                    Bu bir <strong>tahmini gösterge</strong>dir — resmi tatil, yarıyıl/yaz tatili gibi
                    takvim farkları hesaba katılmaz; kesin bordro okul muhasebesine aittir.
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="border-b text-left text-gray-500">
                            <th class="px-6 py-3 font-medium">Öğretmen</th>
                            <th class="px-6 py-3 font-medium">Ders Kırılımı</th>
                            <th class="px-6 py-3 font-medium">Haftalık Saat</th>
                            <th class="px-6 py-3 font-medium">Haftalık Tutar</th>
                            <th class="px-6 py-3 font-medium">Aylık Tutar</th>
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
                                    @if (count($s['dersler']) > 0)
                                        <ul class="space-y-0.5">
                                            @foreach ($s['dersler'] as $d)
                                                <li class="text-xs">
                                                    {{ $d['ders'] }}: {{ $d['saat'] }} saat ×
                                                    {{ number_format($d['saatlikUcret'], 2, ',', '.') }} TL
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-amber-600 text-xs">Programda dersi yok</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 tabular-nums">{{ $s['haftalikSaat'] }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ number_format($s['haftalikTutar'], 2, ',', '.') }} TL</td>
                                <td class="px-6 py-3 tabular-nums font-medium">{{ number_format($s['aylikTutar'], 2, ',', '.') }} TL</td>
                                <td class="px-6 py-3">
                                    @if (! $s['aktif'])
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">Pasif</span>
                                    @elseif ($s['haftalikSaat'] === 0)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">Boşta</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Görevli</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Öğretmen yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
