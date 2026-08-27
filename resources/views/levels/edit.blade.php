<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Seviyeyi Düzenle
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $hata)
                            <li>{{ $hata }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                data-vue-component="SeviyeFormu"
                data-action="{{ route('seviyeler.update', $seviye) }}"
                data-method="PUT"
                data-initial-grade-number="{{ $seviye->grade_number }}"
                data-csrf-token="{{ csrf_token() }}"
            ></div>


            </div>
        </div>
    </div>
</x-app-layout>
