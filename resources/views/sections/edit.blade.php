<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Şubeyi Düzenle
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('subeler.update', $sube) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="level_id" value="Seviye" />
                        <select id="level_id" name="level_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">Seçiniz</option>
                            @foreach ($seviyeler as $seviye)
                                <option value="{{ $seviye->id }}" @selected(old('level_id', $sube->level_id) == $seviye->id)>
                                    {{ $seviye->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('level_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="Şube Adı (örn. 9A)" />
                        <x-text-input id="name" name="name" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('name', $sube->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="room_id" value="Derslik" />
                        <select id="room_id" name="room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">Derslik atanmadı</option>
                            @foreach ($derslikler as $derslik)
                                <option value="{{ $derslik->id }}" @selected(old('room_id', $sube->room_id) == $derslik->id)>
                                    {{ $derslik->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">
                            Boştaki derslikler ve şubenin mevcut dersliği listelenir.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Kaydet</x-primary-button>
                        <a href="{{ route('subeler.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
