<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Program Oluşturma</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Ön kontrol uyarısı --}}
            @if (count($dersliksizSubeler) > 0)
                <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded">
                    <strong>Eksik kurulum:</strong>
                    {{ implode(', ', $dersliksizSubeler) }}
                    şubesine derslik atanmamış. Bu şubelerin programı üretilemez —
                    her program satırı bir derslik gerektiriyor.
                    <a href="{{ route('subeler.index') }}" class="underline">Şubeler ekranından</a>
                    derslik atayın.
                </div>
            @endif

            {{-- ADIM 1 --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-gray-900">1. Adım — Öğretmen Ataması</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Her şube-ders çiftine, o dersi o seviyede verebilen öğretmenler
                            arasından en az yüklü olan atanır.
                        </p>
                    </div>
                    <span class="shrink-0 text-sm {{ $atamaSayisi > 0 ? 'text-green-700' : 'text-gray-500' }}">
                        {{ $atamaSayisi }} atama
                    </span>
                </div>

                <form method="POST" action="{{ route('program.atama') }}"
                      onsubmit="return confirm('Mevcut atamalar silinip yeniden oluşturulacak. Devam edilsin mi?');">
                    @csrf
                    <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Atamaları Oluştur
                    </button>
                </form>
            </div>

            @if ($atamaSonuc)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">1. Adım Sonucu</h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Yapılan atama</div>
                            <div class="text-2xl font-semibold">{{ $atamaSonuc['atanan'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Toplam ders saati</div>
                            <div class="text-2xl font-semibold">{{ $atamaSonuc['toplam_saat'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">En yüklü</div>
                            <div class="text-2xl font-semibold">{{ $atamaSonuc['en_yuklu'] }} saat</div>
                        </div>
                        <div>
                            <div class="text-gray-500">En az yüklü</div>
                            <div class="text-2xl font-semibold">{{ $atamaSonuc['en_az_yuklu'] }} saat</div>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600">
                        {{ $atamaSonuc['calisan_ogretmen'] }} öğretmene iş düştü.
                        Yük farkı <strong>{{ $atamaSonuc['en_yuklu'] - $atamaSonuc['en_az_yuklu'] }} saat</strong>.
                        İşlem {{ $atamaSonuc['sure_ms'] }} ms sürdü.
                    </p>

                    @if (count($atamaSonuc['atanamayanlar']) > 0)
                        <div class="border-t pt-4">
                            <h4 class="text-sm font-medium text-red-800 mb-2">
                                Öğretmen atanamayan dersler ({{ count($atamaSonuc['atanamayanlar']) }})
                            </h4>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="py-2">Şube</th>
                                        <th class="py-2">Ders</th>
                                        <th class="py-2">Saat</th>
                                        <th class="py-2">Sebep</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($atamaSonuc['atanamayanlar'] as $satir)
                                        <tr class="border-b">
                                            <td class="py-2">{{ $satir['sube'] }}</td>
                                            <td class="py-2">{{ $satir['ders'] }}</td>
                                            <td class="py-2">{{ $satir['saat'] }}</td>
                                            <td class="py-2 text-gray-600">{{ $satir['sebep'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ADIM 2 --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 {{ $atamaSayisi === 0 ? 'opacity-60' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-gray-900">2. Adım — Gün ve Saat Yerleştirme</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Her atama, şubenin ve öğretmenin aynı anda boş olduğu bir slota
                            yerleştirilir. Aynı ders mümkün olduğunca farklı günlere dağıtılır.
                        </p>
                    </div>
                    <span class="shrink-0 text-sm {{ $programSayisi > 0 ? 'text-green-700' : 'text-gray-500' }}">
                        {{ $programSayisi }} ders saati
                    </span>
                </div>

                @if ($atamaSayisi === 0)
                    <p class="text-sm text-amber-700">Önce 1. adımı çalıştırın.</p>
                @else
                    <form method="POST" action="{{ route('program.yerlestir') }}"
                          onsubmit="return confirm('Mevcut program silinip yeniden üretilecek. Devam edilsin mi?');">
                        @csrf
                        <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Programı Üret
                        </button>
                    </form>
                @endif
            </div>

            @if ($yerlesimSonuc)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">2. Adım Sonucu</h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Yerleşen</div>
                            <div class="text-2xl font-semibold">{{ $yerlesimSonuc['yerlesen'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Hedef</div>
                            <div class="text-2xl font-semibold">{{ $yerlesimSonuc['hedef'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Slot kapasitesi</div>
                            <div class="text-2xl font-semibold">{{ $yerlesimSonuc['kapasite'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Doluluk</div>
                            <div class="text-2xl font-semibold">%{{ $yerlesimSonuc['doluluk'] }}</div>
                        </div>
                    </div>

                    @php
                        $gunAdlari = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
                    @endphp

                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Günlere dağılım</h4>
                        <div class="flex flex-wrap gap-4 text-sm">
                            @foreach ($yerlesimSonuc['gun_dagilimi'] as $gun => $adet)
                                <div class="text-gray-700">
                                    {{ $gunAdlari[$gun] ?? $gun }}:
                                    <strong>{{ $adet }}</strong> ders
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Sayıların birbirine yakın olması yükün haftaya dengeli yayıldığını gösterir.
                        </p>
                    </div>

                    <p class="text-sm text-gray-600">İşlem {{ $yerlesimSonuc['sure_ms'] }} ms sürdü.</p>

                    @if (count($yerlesimSonuc['atanamayanlar']) > 0)
                        <div class="border-t pt-4">
                            <h4 class="text-sm font-medium text-red-800 mb-2">
                                Yerleştirilemeyen ders saatleri ({{ count($yerlesimSonuc['atanamayanlar']) }} kalem)
                            </h4>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-gray-500">
                                        <th class="py-2">Şube</th>
                                        <th class="py-2">Ders</th>
                                        <th class="py-2">Öğretmen</th>
                                        <th class="py-2">Saat</th>
                                        <th class="py-2">Sebep</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($yerlesimSonuc['atanamayanlar'] as $satir)
                                        <tr class="border-b">
                                            <td class="py-2">{{ $satir['sube'] }}</td>
                                            <td class="py-2">{{ $satir['ders'] }}</td>
                                            <td class="py-2 text-gray-600">{{ $satir['ogretmen'] }}</td>
                                            <td class="py-2">{{ $satir['saat'] }}</td>
                                            <td class="py-2 text-gray-600">{{ $satir['sebep'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-green-50 border border-green-200 rounded p-4 text-sm text-green-800">
                            Tüm ders saatleri yerleştirildi.
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
