<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Yeni Ders Ekle
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $hata)
                                <li>{{ $hata }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div
                    data-vue-component="DersFormu"
                    data-action="{{ route('dersler.store') }}"
                    data-seviyeler="{{ $seviyeler->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toJson() }}"
                    data-csrf-token="{{ csrf_token() }}"
                ></div>

            </div>
        </div>
    </div>
</x-app-layout>
