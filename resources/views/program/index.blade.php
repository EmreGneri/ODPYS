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

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="font-medium text-gray-900">1. Adım — Öğretmen Ataması</h3>

                <p class="text-sm text-gray-600">
                    Her şube-ders çiftine, o dersi o seviyede verebilen öğretmenler arasından
                    en az yüklü olan atanır. Mevcut atama sayısı:
                    <strong>{{ $atamaSayisi }}</strong>
                </p>

                <form method="POST" action="{{ route('program.atama') }}"
                      onsubmit="return confirm('Mevcut atamalar silinip yeniden oluşturulacak. Devam edilsin mi?');">
                    @csrf
                    <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Atamaları Oluştur
                    </button>
                </form>
            </div>

            @if ($sonuc)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <h3 class="font-medium text-gray-900">Sonuç</h3>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Yapılan atama</div>
                            <div class="text-2xl font-semibold">{{ $sonuc['atanan'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Toplam ders saati</div>
                            <div class="text-2xl font-semibold">{{ $sonuc['toplam_saat'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">En yüklü öğretmen</div>
                            <div class="text-2xl font-semibold">{{ $sonuc['en_yuklu'] }} saat</div>
                        </div>
                        <div>
                            <div class="text-gray-500">En az yüklü</div>
                            <div class="text-2xl font-semibold">{{ $sonuc['en_az_yuklu'] }} saat</div>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600">
                        {{ $sonuc['calisan_ogretmen'] }} öğretmene iş düştü.
                        En yüklü ile en az yüklü arasındaki fark
                        <strong>{{ $sonuc['en_yuklu'] - $sonuc['en_az_yuklu'] }} saat</strong> —
                        hakkaniyet ölçütü budur, ne kadar küçükse dağılım o kadar dengeli.
                        İşlem {{ $sonuc['sure_ms'] }} ms sürdü.
                    </p>
                </div>

                @if (count($sonuc['atanamayanlar']) > 0)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-medium text-red-800 mb-3">
                            Atanamayan Dersler ({{ count($sonuc['atanamayanlar']) }})
                        </h3>

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-2">Şube</th>
                                    <th class="py-2">Seviye</th>
                                    <th class="py-2">Ders</th>
                                    <th class="py-2">Saat</th>
                                    <th class="py-2">Sebep</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sonuc['atanamayanlar'] as $satir)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $satir['sube'] }}</td>
                                        <td class="py-2">{{ $satir['seviye'] }}</td>
                                        <td class="py-2">{{ $satir['ders'] }}</td>
                                        <td class="py-2">{{ $satir['saat'] }}</td>
                                        <td class="py-2 text-gray-600">{{ $satir['sebep'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-green-50 border border-green-200 rounded p-4 text-sm text-green-800">
                        Tüm şube-ders çiftlerine öğretmen atandı.
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
