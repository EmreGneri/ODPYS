<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">İzin Taleplerim</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex items-center justify-between">
                <a href="{{ route('ogretmen.panel') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Panelim
                </a>
                <a href="{{ route('ogretmen.izinler.create') }}"
                   class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                    Yeni İzin Talebi
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="border-b text-left text-gray-500">
                            <th class="px-6 py-3 font-medium">Başlangıç</th>
                            <th class="px-6 py-3 font-medium">Bitiş</th>
                            <th class="px-6 py-3 font-medium">Gerekçe</th>
                            <th class="px-6 py-3 font-medium">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($izinler as $izin)
                            <tr>
                                <td class="px-6 py-3 tabular-nums">{{ $izin->starts_at->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-3 tabular-nums">{{ $izin->ends_at->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $izin->reason ?: '—' }}</td>
                                <td class="px-6 py-3">
                                    @if ($izin->status === 'pending')
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">Bekliyor</span>
                                    @elseif ($izin->status === 'approved')
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Onaylandı</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs">Reddedildi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    Henüz izin talebiniz yok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
