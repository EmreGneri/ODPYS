<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>ODPYS API Dokümanı</title>

    {{-- Swagger UI dosyalari YEREL - sunucunun internet erisimi yok,
         CDN calismazdi. public/swagger/ altinda duruyorlar. --}}
    <link rel="stylesheet" href="{{ asset('swagger/swagger-ui.css') }}">

    <style>
        body { margin: 0; background: #fafafa; }

        .ust-bant {
            background: #1f2937;
            color: #fff;
            padding: 12px 20px;
            font: 14px/1.5 system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .ust-bant a { color: #93c5fd; }
        .ust-bant .not { color: #9ca3af; font-size: 13px; }

        /* Swagger UI kendi basligini basiyor; ustteki bandimiz yeterli. */
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>

<div class="ust-bant">
    <div>
        <strong>ODPYS API Dokümanı</strong>
        <span class="not">— {{ auth()->user()->name }} olarak giriş yapıldı</span>
    </div>
    <div class="not">
        “Try it out” düğmeleri oturumunuzla gerçek isteği gönderir.
        <a href="/app">&larr; Uygulamaya dön</a>
    </div>
</div>

<div id="swagger"></div>

<script src="{{ asset('swagger/swagger-ui-bundle.js') }}"></script>
<script src="{{ asset('swagger/swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function () {
        SwaggerUIBundle({
            url: '{{ route('api-dokuman.json') }}',
            dom_id: '#swagger',
            deepLinking: true,
            presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
            layout: 'StandaloneLayout',
            docExpansion: 'none',        // konu başlıkları kapalı başlasın
            defaultModelsExpandDepth: -1, // alttaki şema listesini gizle

            // Oturum çerezi gönderilsin - "Try it out" giriş yapmış
            // kullanıcının yetkisiyle çalışsın diye.
            withCredentials: true,

            requestInterceptor: function (istek) {
                // Laravel POST/PUT/DELETE isteklerinde CSRF token
                // bekliyor. Çerezdeki XSRF-TOKEN'ı başlığa taşıyoruz -
                // normalde bu işi axios yapıyor (bkz. resources/js/api.js),
                // burada elle yapmak gerekiyor.
                var eslesme = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

                if (eslesme) {
                    istek.headers['X-XSRF-TOKEN'] = decodeURIComponent(eslesme[1]);
                }

                return istek;
            },
        });
    };
</script>

</body>
</html>
