<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ders Atama — {{ $ogretmen->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-6 rounded bg-red-50 border border-red-200 p-4">
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $hata)
                                <li>{{ $hata }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-sm text-gray-600 mb-6">
                    Bu öğretmenin hangi dersi hangi seviyede verebileceğini işaretleyin.
                    Program sihirbazı yalnızca burada yetkilendirilmiş öğretmenleri değerlendirir.
                    Ders adının yanındaki kutu tüm seviyeleri, seviye başlığındaki kutu
                    tüm dersleri birden seçer.
                </p>

                <div
                    data-vue-component="OgretmenDersleriFormu"
                    data-action="{{ route('ogretmenler.dersler.kaydet', $ogretmen) }}"
                    data-method="PUT"
                    data-dersler="{{ $dersler->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->toJson() }}"
                    data-seviyeler="{{ $seviyeler->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->toJson() }}"
                    data-initial-secili="{{ json_encode(array_keys($mevcut)) }}"
                    data-csrf-token="{{ csrf_token() }}"
                ></div>

            </div>
        </div>
    </div>
</x-app-layout>
