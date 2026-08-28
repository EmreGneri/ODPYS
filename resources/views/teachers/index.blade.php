@use(Illuminate\Support\Facades\Storage)

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Öğretmenler</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded bg-green-100 border border-green-200 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded bg-red-100 border border-red-200 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                <form method="GET" action="{{ route('ogretmenler.index') }}" class="flex flex-wrap gap-3">
                    <input type="text" name="ara" value="{{ request('ara') }}"
                           placeholder="Ad, soyad, e-posta veya TC"
                           class="rounded-md border-gray-300 shadow-sm text-sm w-72">

                    <select name="durum" class="rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Tümü</option>
                        <option value="aktif" @selected(request('durum') === 'aktif')>Aktif</option>
                        <option value="pasif" @selected(request('durum') === 'pasif')>Pasif</option>
                    </select>

                    <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                        Filtrele
                    </button>

                    @if (request('ara') || request('durum'))
                        <a href="{{ route('ogretmenler.index') }}"
                           class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Temizle</a>
                    @endif
                </form>

                <a href="{{ route('ogretmenler.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                    Yeni Öğretmen
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Ad Soyad</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">TC Kimlik</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">E-posta</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Azami Saat</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Özgeçmiş</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Durum</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($ogretmenler as $ogretmen)
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($ogretmen->photo_path)
                                            <img src="{{ Storage::url($ogretmen->photo_path) }}" alt=""
                                                 class="h-9 w-9 rounded-full object-cover">
                                        @else
                                            <div class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500">
                                                {{ mb_substr($ogretmen->first_name, 0, 1) }}{{ mb_substr($ogretmen->last_name, 0, 1) }}
                                            </div>
                                        @endif
                                        <span>{{ $ogretmen->full_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-gray-600">{{ $ogretmen->national_id }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $ogretmen->email }}</td>
                                <td class="px-6 py-3">{{ $ogretmen->max_weekly_hours }}</td>
                                <td class="px-6 py-3">
                                    @if ($ogretmen->cv_path)
                                        <a href="{{ Storage::url($ogretmen->cv_path) }}" target="_blank"
                                           class="text-indigo-600 hover:text-indigo-900">PDF</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    @if ($ogretmen->is_active)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">Aktif</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">Pasif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right space-x-3 whitespace-nowrap">
                                    <a href="{{ route('ogretmenler.edit', $ogretmen) }}"
                                       class="text-indigo-600 hover:text-indigo-900">Düzenle</a>

                                    <form action="{{ route('ogretmenler.destroy', $ogretmen) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('{{ $ogretmen->full_name }} silinsin mi?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    @if (request('ara') || request('durum'))
                                        Aramanızla eşleşen öğretmen bulunamadı.
                                    @else
                                        Henüz öğretmen eklenmemiş.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $ogretmenler->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
