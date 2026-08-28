<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Öğretmen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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

                <div
                    data-vue-component="OgretmenFormu"
                    data-action="{{ route('ogretmenler.store') }}"
                    data-method="POST"
                    data-azami-saat="{{ $azamiSaat }}"
                    data-initial-first-name="{{ old('first_name') }}"
                    data-initial-last-name="{{ old('last_name') }}"
                    data-initial-national-id="{{ old('national_id') }}"
                    data-initial-birth-date="{{ old('birth_date') }}"
                    data-initial-email="{{ old('email') }}"
                    data-initial-max-weekly-hours="{{ old('max_weekly_hours', 30) }}"
                    data-initial-is-active="{{ old('is_active', 1) }}"
                    data-csrf-token="{{ csrf_token() }}"
                ></div>
            </div>
        </div>
    </div>
</x-app-layout>
