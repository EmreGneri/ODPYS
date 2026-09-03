<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * routes/api.php icindeki rotalardan OpenAPI 3 dokumani uretir.
 *
 * NEDEN ELLE YAZILMIYOR?
 * Elle yazilan bir API dokumani, kod degistikce kacinilmaz olarak
 * gercekle ayrisir - bir uc nokta eklenir ama dokumana yazilmaz,
 * bir alan silinir ama dokumanda kalir. Burada dokuman, LARAVEL'IN
 * KENDI ROTA TABLOSUNDAN uretiliyor: yeni bir rota eklendiginde
 * komutu tekrar calistirmak yeterli, doküman kendiliginden dogru
 * oluyor.
 *
 * NEREDEN NE OKUNUYOR?
 *   - adres, HTTP metodu, rota parametreleri -> Route::getRoutes()
 *   - kimlik dogrulama ve rol gereksinimi    -> rotanin middleware'leri
 *   - istek govdesi alanlari                 -> controller metodunun
 *     tip belirttigi FormRequest'in rules() metodu (REFLECTION ile)
 *   - insan diliyle aciklamalar              -> config/api-dokuman.php
 *
 * Ucuncu madde en degerlisi: dogrulama kurallari zaten FormRequest'te
 * yaziyor, dokuman onu tekrar etmiyor - ayni kaynaktan okuyor. Kural
 * degisirse dokuman da degisiyor.
 */
class ApiDokumaniUret extends Command
{
    protected $signature = 'api:dokuman';

    protected $description = 'API rotalarindan OpenAPI 3 dokumani uretir (public/openapi.json)';

    /**
     * Adresin ilk parcasina gore konu basligi. Swagger UI uc noktalari
     * bu basliklara gore gruplayip katlanabilir bloklar halinde
     * gosteriyor.
     */
    private const BASLIKLAR = [
        'login' => 'Kimlik Doğrulama',
        'logout' => 'Kimlik Doğrulama',
        'user' => 'Kimlik Doğrulama',
        'forgot-password' => 'Kimlik Doğrulama',
        'reset-password' => 'Kimlik Doğrulama',
        'profil' => 'Profil',
        'ogretmen' => 'Öğretmen Paneli',
        'dashboard' => 'Dashboard',
        'okul' => 'Okul Ayarları',
        'okullar' => 'Çoklu Okul',
        'yoneticiler' => 'Yönetici Hesapları',
        'seviyeler' => 'Seviyeler',
        'subeler' => 'Şubeler',
        'derslikler' => 'Derslikler',
        'dersler' => 'Dersler',
        'ogretmenler' => 'Öğretmenler',
        'program' => 'Program Sihirbazı',
        'cizelge' => 'Çizelge',
        'ucretler' => 'Ücretler',
        'izinler' => 'İzinler ve Takas',
        'gezinme' => 'Yardımcı',
    ];

    public function handle(): int
    {
        $aciklamalar = config('api-dokuman.aciklamalar', []);

        $yollar = [];
        $kullanilanBasliklar = [];
        $rotaSayisi = 0;
        $govdeCikarilan = 0;

        foreach (Route::getRoutes() as $rota) {
            $uri = $rota->uri();

            if (! Str::startsWith($uri, 'api/')) {
                continue;
            }

            foreach ($rota->methods() as $metot) {
                // HEAD ve OPTIONS'i Laravel otomatik ekliyor; dokumanda
                // gosterilmeleri gurultu olur.
                if (in_array($metot, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $rotaSayisi++;

                // Laravel: api/izinler/{izin}  ->  OpenAPI: /api/izinler/{izin}
                $yol = '/' . $uri;
                $baslik = $this->baslikBul($uri);
                $kullanilanBasliklar[$baslik] = true;

                $anahtar = $metot . ' ' . $uri;
                $aciklama = $aciklamalar[$anahtar] ?? null;

                $islem = [
                    'tags' => [$baslik],
                    'summary' => $aciklama['ozet'] ?? $this->ozetUret($metot, $uri),
                    'operationId' => $rota->getName() ?: Str::slug($anahtar),
                    'parameters' => $this->parametreler($rota, $aciklama),
                    'responses' => $this->cevaplar($rota),
                ];

                if (! empty($aciklama['aciklama'])) {
                    $islem['description'] = $aciklama['aciklama'];
                }

                $yetki = $this->yetkiNotu($rota);

                if ($yetki) {
                    $islem['description'] = trim(($islem['description'] ?? '') . "\n\n**Yetki:** " . $yetki);
                }

                if ($this->kimlikGerekiyorMu($rota)) {
                    $islem['security'] = [['oturumCerezi' => []], ['bearerToken' => []]];
                }

                if (in_array($metot, ['POST', 'PUT', 'PATCH'], true)) {
                    $govde = $this->istekGovdesi($rota, $aciklama);

                    if ($govde) {
                        $islem['requestBody'] = $govde;
                        $govdeCikarilan++;
                    }
                }

                $yollar[$yol][strtolower($metot)] = $islem;
            }
        }

        ksort($yollar);

        $belge = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'ODPYS API',
                'version' => '1.0.0',
                'description' => config('api-dokuman.giris', ''),
            ],
            'servers' => [
                ['url' => rtrim(config('app.url'), '/'), 'description' => 'Uygulama sunucusu'],
            ],
            'tags' => array_map(
                fn ($ad) => ['name' => $ad],
                array_keys($kullanilanBasliklar)
            ),
            'components' => [
                'securitySchemes' => [
                    // SPA'nin kullandigi yol: oturum cerezi + CSRF.
                    'oturumCerezi' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'laravel_session',
                        'description' => 'SPA bu yolu kullanır. Tarayıcıda giriş yaptıysanız '
                            . '"Try it out" doğrudan çalışır — çerez otomatik gönderilir.',
                    ],
                    // Harici istemcilerin (Postman, mobil) kullandigi yol.
                    'bearerToken' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Harici istemciler için. POST /api/login ile token alınır.',
                    ],
                ],
            ],
            'paths' => $yollar,
        ];

        // public/ DEGIL storage/: public'e koysaydik dosya web sunucusu
        // tarafindan dogrudan, yetki kontrolu olmadan servis edilirdi.
        // API'nin butun yuzeyini herkese acmaya gerek yok - doküman
        // yonetici girisi arkasindan sunuluyor (bkz. routes/web.php).
        $hedef = storage_path('app/openapi.json');

        file_put_contents(
            $hedef,
            json_encode($belge, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $this->info("OpenAPI dokumani uretildi: {$hedef}");
        $this->line("  Uc nokta      : {$rotaSayisi}");
        $this->line('  Konu basligi  : ' . count($kullanilanBasliklar));
        $this->line("  Govde semasi  : {$govdeCikarilan} (FormRequest'ten cikarildi)");

        // Aciklamasi olmayan uc noktalari bildir - dokumanin eksik
        // kalan kismini gormek icin.
        $eksik = [];

        foreach (Route::getRoutes() as $rota) {
            if (! Str::startsWith($rota->uri(), 'api/')) {
                continue;
            }

            foreach ($rota->methods() as $metot) {
                if (in_array($metot, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                if (! isset($aciklamalar[$metot . ' ' . $rota->uri()])) {
                    $eksik[] = $metot . ' ' . $rota->uri();
                }
            }
        }

        if ($eksik) {
            $this->newLine();
            $this->warn('Elle aciklamasi olmayan ' . count($eksik) . ' uc nokta var');
            $this->line('(otomatik uretilen ozet kullanildi - config/api-dokuman.php ile zenginlestirilebilir)');

            foreach (array_slice($eksik, 0, 10) as $e) {
                $this->line('  - ' . $e);
            }

            if (count($eksik) > 10) {
                $this->line('  ... ve ' . (count($eksik) - 10) . ' tane daha');
            }
        }

        return self::SUCCESS;
    }

    private function baslikBul(string $uri): string
    {
        // "api/izinler/{izin}/takas" -> "izinler"
        $parcalar = explode('/', $uri);
        $ilk = $parcalar[1] ?? '';

        return self::BASLIKLAR[$ilk] ?? 'Diğer';
    }

    /**
     * Elle aciklama yazilmamis uc noktalar icin okunabilir bir ozet
     * uretir - bos birakmaktansa.
     */
    private function ozetUret(string $metot, string $uri): string
    {
        $fiil = match ($metot) {
            'GET' => 'Listele / getir',
            'POST' => 'Oluştur',
            'PUT', 'PATCH' => 'Güncelle',
            'DELETE' => 'Sil',
            default => $metot,
        };

        return $fiil . ' — ' . $uri;
    }

    /**
     * Rota parametrelerini ({izin}, {sube} gibi) OpenAPI parametresine
     * cevirir. Hepsi zorunlu ve yolun icinde (path parameter).
     */
    private function parametreler($rota, ?array $aciklama): array
    {
        $sonuc = [];

        preg_match_all('/\{(\w+)\??\}/', $rota->uri(), $eslesmeler);

        foreach ($eslesmeler[1] as $ad) {
            $sonuc[] = [
                'name' => $ad,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer'],
                'description' => $aciklama['parametreler'][$ad] ?? ($ad . ' kaydının id değeri'),
            ];
        }

        // GET uc noktalarinin sorgu parametreleri elle tanimlaniyor -
        // kodda ($request->input(...)) olduklari icin otomatik
        // cikarilamiyorlar.
        foreach ($aciklama['sorgu'] ?? [] as $ad => $tanim) {
            $sonuc[] = [
                'name' => $ad,
                'in' => 'query',
                'required' => $tanim['zorunlu'] ?? false,
                'schema' => ['type' => $tanim['tip'] ?? 'string'],
                'description' => $tanim['aciklama'] ?? '',
            ];
        }

        return $sonuc;
    }

    private function kimlikGerekiyorMu($rota): bool
    {
        foreach ($rota->gatherMiddleware() as $m) {
            if (is_string($m) && Str::startsWith($m, 'auth:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rotanin middleware'lerinden rol gereksinimini insan diline cevirir.
     * Bu bilgi rota tanimindan geliyor, elle yazilmiyor - yetki
     * degisirse dokuman da degisiyor.
     */
    private function yetkiNotu($rota): ?string
    {
        $mw = $rota->gatherMiddleware();

        if (in_array('superadmin', $mw, true)) {
            return 'Yalnızca süper yönetici';
        }

        if (in_array('admin', $mw, true)) {
            return 'Yönetici (admin veya superadmin)';
        }

        if (in_array('teacher', $mw, true)) {
            return 'Yalnızca öğretmen';
        }

        if ($this->kimlikGerekiyorMu($rota)) {
            return 'Giriş yapmış her kullanıcı';
        }

        return null;
    }

    private function cevaplar($rota): array
    {
        $sonuc = [
            '200' => ['description' => 'Başarılı'],
        ];

        if ($this->kimlikGerekiyorMu($rota)) {
            $sonuc['401'] = ['description' => 'Giriş yapılmamış'];
            $sonuc['403'] = ['description' => 'Yetki yok'];
        }

        if (Str::contains($rota->uri(), '{')) {
            $sonuc['404'] = ['description' => 'Kayıt bulunamadı'];
        }

        $metotlar = array_diff($rota->methods(), ['HEAD', 'OPTIONS']);

        if (array_intersect($metotlar, ['POST', 'PUT', 'PATCH'])) {
            $sonuc['422'] = ['description' => 'Doğrulama hatası — errors nesnesi döner'];
        }

        return $sonuc;
    }

    /**
     * Controller metodunun tip belirttigi FormRequest'i bulup rules()
     * metodundan istek govdesi semasi cikarir.
     *
     * Reflection nedir: PHP'nin calisma aninda "bu sinifin metotlari
     * neler, parametreleri hangi tipte" diye kendine bakabilmesi.
     * Burada controller metodunun imzasina bakip FormRequest'i
     * buluyoruz.
     *
     * rules() bazi FormRequest'lerde route parametresine ya da aktif
     * okula bagli (ornegin CourseRequest). Konsolda boyle bir istek
     * baglami olmadigi icin hata firlatabilir - o yuzden try/catch
     * icinde, basarisiz olursa sema uretilmiyor, dokuman yine
     * calisiyor.
     */
    private function istekGovdesi($rota, ?array $aciklama): ?array
    {
        // Elle yazilmis sema varsa o kazanir.
        if (! empty($aciklama['govde'])) {
            return [
                'required' => true,
                'content' => ['application/json' => ['schema' => $aciklama['govde']]],
            ];
        }

        $eylem = $rota->getActionName();

        if (! Str::contains($eylem, '@')) {
            return null;
        }

        [$sinif, $metot] = explode('@', $eylem);

        try {
            $yansima = new ReflectionMethod($sinif, $metot);
        } catch (Throwable) {
            return null;
        }

        foreach ($yansima->getParameters() as $parametre) {
            $tip = $parametre->getType();

            if (! $tip instanceof ReflectionNamedType || $tip->isBuiltin()) {
                continue;
            }

            $tipAdi = $tip->getName();

            if (! is_subclass_of($tipAdi, FormRequest::class)) {
                continue;
            }

            try {
                $kurallar = (new $tipAdi)->rules();
            } catch (Throwable) {
                // rules() baglam istiyor - sema uretemiyoruz, sorun degil.
                return null;
            }

            return [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $this->kurallariSemayaCevir($kurallar),
                    ],
                ],
            ];
        }

        return null;
    }

    /**
     * Laravel dogrulama kurallarini OpenAPI semasina cevirir.
     * Ornek: 'name' => 'required|string|max:255'
     *     -> {"type":"string","maxLength":255} + required listesine "name"
     */
    private function kurallariSemayaCevir(array $kurallar): array
    {
        $ozellikler = [];
        $zorunlular = [];

        foreach ($kurallar as $alan => $kural) {
            // "seviyeler.*.weekly_hours" gibi ic ice alanlari duz
            // gosteriyoruz - OpenAPI'de tam karsiligini uretmek
            // buradaki degerinden fazla is olurdu.
            $parcalar = is_string($kural) ? explode('|', $kural) : $kural;

            $sema = ['type' => 'string'];
            $zorunlu = false;

            foreach ($parcalar as $p) {
                // Rule nesneleri (Rule::unique(), Password::defaults())
                // metin degil - atliyoruz.
                if (! is_string($p)) {
                    continue;
                }

                $ad = Str::before($p, ':');
                $deger = Str::contains($p, ':') ? Str::after($p, ':') : null;

                match ($ad) {
                    'required' => $zorunlu = true,
                    'integer' => $sema['type'] = 'integer',
                    'numeric' => $sema['type'] = 'number',
                    'boolean' => $sema['type'] = 'boolean',
                    'array' => $sema['type'] = 'array',
                    'email' => $sema['format'] = 'email',
                    'date' => $sema['format'] = 'date-time',
                    'nullable' => $sema['nullable'] = true,
                    'max' => $this->sinirYaz($sema, 'max', $deger),
                    'min' => $this->sinirYaz($sema, 'min', $deger),
                    'exists' => $sema['type'] = 'integer',
                    'confirmed' => $sema['description'] = 'Ayrıca {alan}_confirmation gönderilmeli',
                    default => null,
                };
            }

            if (($sema['type'] ?? null) === 'array') {
                $sema['items'] = ['type' => 'object'];
            }

            $ozellikler[$alan] = $sema;

            if ($zorunlu) {
                $zorunlular[] = $alan;
            }
        }

        $sonuc = ['type' => 'object', 'properties' => $ozellikler];

        if ($zorunlular) {
            $sonuc['required'] = $zorunlular;
        }

        return $sonuc;
    }

    /**
     * Laravel'de max/min kuralinin anlami alanin tipine gore degisiyor:
     * metinde karakter sayisi, sayida deger. OpenAPI'de bunlar ayri
     * anahtarlar.
     */
    private function sinirYaz(array &$sema, string $yon, ?string $deger): void
    {
        if ($deger === null || ! is_numeric($deger)) {
            return;
        }

        $sayisal = in_array($sema['type'] ?? 'string', ['integer', 'number'], true);

        if ($yon === 'max') {
            $anahtar = $sayisal ? 'maximum' : 'maxLength';
        } else {
            $anahtar = $sayisal ? 'minimum' : 'minLength';
        }

        $sema[$anahtar] = (int) $deger;
    }
}
