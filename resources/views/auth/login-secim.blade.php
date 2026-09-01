<x-guest-layout>
    <h2 class="text-lg font-semibold text-gray-800 mb-6 text-center">Nasıl giriş yapmak istiyorsunuz?</h2>

    <div class="space-y-3">
        <a href="{{ route('login.form', 'yonetici') }}"
           class="block w-full text-center px-4 py-3 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
            Yönetici Girişi
        </a>

        <a href="{{ route('login.form', 'ogretmen') }}"
           class="block w-full text-center px-4 py-3 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
            Öğretmen Girişi
        </a>
    </div>
</x-guest-layout>
