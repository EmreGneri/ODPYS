<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dersler
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div>
                <a href="{{ route('dersler.create') }}"
                   class="inline-block px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                    Yeni Ders Ekle
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Ders</th>
                            <th class="py-2">Saatlik Ücret</th>
                            <th class="py-2">Seviyeler ve Haftalık Saatler</th>
                            <th class="py-2 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dersler as $ders)
                            <tr class="border-b">
                                <td class="py-2 align-top">{{ $ders->name }}</td>
                                <td class="py-2 align-top">{{ number_format($ders->hourly_rate, 2, ',', '.') }} TL</td>
                                <td class="py-2 align-top">
                                    @forelse ($ders->courseLevels as $cl)
                                        <div class="text-gray-700">
                                            {{ $cl->level->name }} — {{ $cl->weekly_hours }} saat
                                        </div>
                                    @empty
                                        <span class="text-gray-400">Seviye atanmamış</span>
                                    @endforelse
                                </td>
                                <td class="py-2 text-right align-top space-x-2">
                                    <a href="{{ route('dersler.edit', $ders) }}"
                                       class="text-blue-600 hover:underline">Düzenle</a>

                                    <form method="POST" action="{{ route('dersler.destroy', $ders) }}"
                                          class="inline"
                                          onsubmit="return confirm('Bu dersi silmek istediğinize emin misiniz? Seviye atamaları da silinecek.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
