<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">İzin Talepleri</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="border-b text-left text-gray-500">
                            <th class="px-6 py-3 font-medium">Öğretmen</th>
                            <th class="px-6 py-3 font-medium">Başlangıç</th>
                            <th class="px-6 py-3 font-medium">Bitiş</th>
                            <th class="px-6 py-3 font-medium">Gerekçe</th>
                            <th class="px-6 py-3 font-medium">Durum</th>
                            <th class="px-6 py-3 text-right font-medium">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($izinler as $izin)
                            <tr class="{{ $izin->status === 'pending' ? 'bg-amber-50' : '' }}">
                                <td class="px-6 py-3">{{ $izin->teacher->full_name }}</td>
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
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('izinler.goster', $izin) }}"
                                       class="text-indigo-600 hover:text-indigo-900 text-sm">
                                        @if ($izin->status === 'pending')
                                            İncele
                                        @else
                                            Detay
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Henüz izin talebi yok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
