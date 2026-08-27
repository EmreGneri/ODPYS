<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Seviyeler
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div>
                <a href="{{ route('seviyeler.create') }}"
                   class="inline-block px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                    Yeni Seviye Ekle
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Ad</th>
                            <th class="py-2">Şube Sayısı</th>
                            <th class="py-2 text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seviyeler as $seviye)
                            <tr class="border-b">
                                <td class="py-2">{{ $seviye->name }}</td>
                                <td class="py-2">{{ $seviye->sections_count }}</td>
                                <td class="py-2 text-right space-x-2">
                                    <a href="{{ route('seviyeler.edit', $seviye) }}"
                                       class="text-blue-600 hover:underline">Düzenle</a>

                                    <form method="POST" action="{{ route('seviyeler.destroy', $seviye) }}"
                                          class="inline"
                                          onsubmit="return confirm('Bu seviyeyi silmek istediğinize emin misiniz? Bağlı şubeler de silinecek.');">
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
