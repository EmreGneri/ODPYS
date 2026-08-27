<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Okul Ayarları
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                        @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div>
                <a href="{{ route('okul.edit') }}"
                   class="inline-block px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                    Düzenle
                </a>
            </div>

            {{-- Okul bilgileri --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">{{ $okul->name }}</h3>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">İl / İlçe</div>
                        <div>{{ $okul->city }} / {{ $okul->district }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Günde ders sayısı</div>
                        <div>{{ $okul->lessons_per_day }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Ders süresi</div>
                        <div>{{ $okul->lesson_duration }} dakika</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Mola süresi</div>
                        <div>{{ $okul->break_duration }} dakika</div>
                    </div>
                </div>
            </div>

            {{-- Eğitim günleri --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Eğitim Günleri</h3>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Gün</th>
                            <th class="py-2">Başlangıç Saati</th>
                            <th class="py-2">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($okul->days as $gun)
                            <tr class="border-b">
                                <td class="py-2">{{ $gunAdlari[$gun->weekday] }}</td>
                                <td class="py-2">{{ substr($gun->start_time, 0, 5) }}</td>
                                <td class="py-2">
                                    @if ($gun->is_active)
                                        Aktif
                                    @else
                                        Pasif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
