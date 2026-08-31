<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">İzin Talebi İncele</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('izinler.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                &larr; İzin Talepleri
            </a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-4">
                    <div>
                        <div class="text-gray-500">Öğretmen</div>
                        <div class="font-medium">{{ $izin->teacher->full_name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Başlangıç</div>
                        <div class="font-medium tabular-nums">{{ $izin->starts_at->format('d.m.Y H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Bitiş</div>
                        <div class="font-medium tabular-nums">{{ $izin->ends_at->format('d.m.Y H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Durum</div>
                        <div class="font-medium">
                            @if ($izin->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">Bekliyor</span>
                            @elseif ($izin->status === 'approved')
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Onaylandı</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs">Reddedildi</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($izin->reason)
                    <div class="text-sm text-gray-700 border-t pt-4">
                        <span class="text-gray-500">Gerekçe:</span> {{ $izin->reason }}
                    </div>
                @endif
            </div>

            {{-- Sartname 2.7.2: "dersi var/yok gosterimi ile" onaylanmali --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-1">Bu Aralıktaki Dersleri</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Programın kendisi değişmiyor; sadece bu tarihlerdeki hangi
                    derslerin etkileneceğini gösteriyorum.
                </p>

                @if (empty($etkilenenDersler))
                    <div class="bg-green-50 border border-green-200 rounded p-4 text-sm text-green-800">
                        Bu aralıkta öğretmenin dersi yok. Program etkilenmiyor.
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="py-2">Tarih</th>
                                <th class="py-2">Saat</th>
                                <th class="py-2">Şube</th>
                                <th class="py-2">Ders</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($etkilenenDersler as $d)
                                <tr>
                                    <td class="py-2">{{ $d['tarih'] }} {{ $d['gunAdi'] }}</td>
                                    <td class="py-2 tabular-nums">{{ $d['baslangic'] }}-{{ $d['bitis'] }}</td>
                                    <td class="py-2">{{ $d['sube'] }}</td>
                                    <td class="py-2">{{ $d['ders'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-500 mt-3">
                        Toplam {{ count($etkilenenDersler) }} ders saati etkileniyor.
                    </p>
                @endif
            </div>

            @if ($izin->status === 'pending')
                <div class="flex items-center gap-4">
                    <form method="POST" action="{{ route('izinler.onayla', $izin) }}"
                          onsubmit="return confirm('Bu izin talebi onaylansın mı?');">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            Onayla
                        </button>
                    </form>

                    <form method="POST" action="{{ route('izinler.reddet', $izin) }}"
                          onsubmit="return confirm('Bu izin talebi reddedilsin mi?');">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                            Reddet
                        </button>
                    </form>
                </div>
            @else
                <div class="text-sm text-gray-500">
                    Bu talep {{ $izin->decided_at?->format('d.m.Y H:i') }} tarihinde
                    {{ $izin->approver?->name ?? 'bir yönetici' }} tarafından karara bağlandı.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
