<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Okullar</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Sistemde tanımlı tüm okullar. Aktif okul, tüm ekranlarda
                    (öğretmenler, dersler, program) görülen ve değiştirilen okuldur.
                </p>
                <a href="{{ route('okullar.create') }}"
                   class="shrink-0 px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                    Yeni Okul Ekle
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="border-b text-left text-gray-500">
                            <th class="px-6 py-3 font-medium">Okul</th>
                            <th class="px-6 py-3 font-medium">İl / İlçe</th>
                            <th class="px-6 py-3 font-medium">Seviye</th>
                            <th class="px-6 py-3 font-medium">Öğretmen</th>
                            <th class="px-6 py-3 font-medium">Durum</th>
                            <th class="px-6 py-3 text-right font-medium">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($okullar as $okul)
                            <tr class="{{ $okul->id === $aktifId ? 'bg-indigo-50' : '' }}">
                                <td class="px-6 py-3 font-medium">{{ $okul->name }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $okul->city }} / {{ $okul->district }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ $okul->seviye_sayisi }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ $okul->ogretmen_sayisi }}</td>
                                <td class="px-6 py-3">
                                    @if ($okul->id === $aktifId)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-xs">Aktif</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if ($okul->id !== $aktifId)
                                        <form method="POST" action="{{ route('okullar.aktif', $okul) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                                Aktif Yap
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-300 text-sm">Şu an aktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Henüz okul tanımlanmamış.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500">
                Aktif okulu değiştirmek, o oturumdaki tüm ekranların (şubeler,
                öğretmenler, program, çizelge) hangi okula ait veriyi göstereceğini
                belirler. Okullar arası veri karışmaz — her okulun kendi seviye,
                şube, ders, öğretmen ve programı ayrı tutulur.
            </p>

        </div>
    </div>
</x-app-layout>
