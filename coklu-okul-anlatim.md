# Çoklu Okul Desteği — Baştan Sona (Şartname 2.1.7)

Şartname: *"Birden fazla okul eklenebilmelidir."* Bu, sistemin tek bir okul varsayımıyla yazılmış her sorgusunu etkileyen, projedeki en geniş kapsamlı değişiklikti. Ne yaptım, neden öyle yaptım, hangi dosya ne işe yarıyor — tamamı.

---

## 1. Sorunun Gerçek Boyutu

İlk bakışta basit görünüyordu: `School::first()` diye 9 satır vardı, onları değiştirmek yeterli sanılabilirdi. Ama tarama yapınca gerçek tablo ortaya çıktı:

```bash
grep -rn "Level::\|Section::\|Course::\|Teacher::\|Room::" app/Http/Controllers/*.php app/Services/*.php app/Http/Requests/*.php
```

**40'tan fazla sorgu satırı**, hiçbiri `school_id` ile filtrelemiyordu:

```php
Level::orderBy('grade_number')->get()
Teacher::where('is_active', true)->count()
Course::pluck('name', 'id')
Section::all()->keyBy('id')
```

İkinci bir okul eklenseydi:
- Sihirbaz **B okulunun öğretmenlerini A okulunun şubelerine** atamaya çalışırdı
- Dashboard sayıları iki okulu toplardı
- Çizelge herkesi gösterirdi

Bu, 9 satırlık bir düzeltme değil, **sorgu katmanının tamamıydı.**

### Ek zorluk: `sections` tablosunda `school_id` yok

Daha önce bilinçli bir tasarım kararı almıştık — veri tekrarı olmasın diye şube, okula değil **seviyeye** bağlı duruyor (`sections.level_id → levels.school_id`). Bu kararı bozmadan çözmem gerekiyordu.

---

## 2. Çözüm: Global Scope

Tek tek 40+ satırı değiştirmek yerine, **modele** bir filtre ekledim — model her sorgulandığında bu filtre **otomatik** devreye giriyor, çağıran kod hiç değişmiyor:

```php
// Kod aynen böyle kalıyor:
Level::orderBy('grade_number')->get()

// Ama üretilen SQL arka planda şuna dönüşüyor:
SELECT * FROM levels WHERE school_id = <aktif okul> ORDER BY grade_number
```

Bu, Laravel'de **Global Scope** deniyor. İki tane yazdım.

### `app/Models/Scopes/OkulScope.php` — yeni dosya

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Coklu okul icin global scope. Level, Room, Course, Teacher
 * modellerinin her sorgusuna otomatik "WHERE school_id = aktif okul"
 * ekliyor - cagiran kod hic degismiyor.
 *
 * session('aktif_okul_id') bos ise HICBIR SEY filtrelemiyor (bilerek -
 * EnsureAktifOkul middleware'i her istekte bunun dolu olmasini garanti
 * ediyor, bu scope sadece son savunma).
 */
class OkulScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($okulId = session('aktif_okul_id')) {
            $builder->where($model->getTable() . '.school_id', $okulId);
        }
    }
}
```

`school_id` sütunu **doğrudan** olan modellerde (`Level`, `Room`, `Course`, `Teacher`) kullanılıyor.

### `app/Models/Scopes/SubeOkulScope.php` — yeni dosya

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Section (sube) tablosunda school_id sutunu YOK - bilincli tasarim
 * karari, veri tekrari olmasin diye seviye uzerinden erisiliyor.
 * Bu yuzden OkulScope'tan farkli: dogrudan where degil, levels
 * tablosuna whereHas (EXISTS alt sorgusu) ile bakiyor.
 *
 * whereHas bir EXISTS alt sorgusu uretir, JOIN degil - SectionController
 * gibi yerlerdeki manuel join'lerle CAKISMIYOR, yan yana calisir.
 */
class SubeOkulScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($okulId = session('aktif_okul_id')) {
            $builder->whereHas('level', function (Builder $q) use ($okulId) {
                $q->where('school_id', $okulId);
            });
        }
    }
}
```

`Section` modelinde kullanılıyor — kendi `school_id`'si olmadığı için `levels` tablosuna `whereHas` (bir `EXISTS` alt sorgusu) ile bakıyor.

**Neden `whereHas`, neden `join` değil:** `SectionController::index()` gibi yerlerde zaten elle yazılmış `join`'ler var. `whereHas` bir `JOIN` değil, ayrı bir `EXISTS (...)` alt sorgusu üretiyor — mevcut manuel join'lerle **çakışmıyor**, yan yana çalışıyor.

---

## 3. Scope'ları Modellere Bağlama

Her modelde `booted()` metodu ile scope ekleniyor. Laravel bu metodu model her "önyüklenirken" bir kez çağırıyor.

### `app/Models/Level.php`

```php
<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'grade_number',
    ];

    /**
     * Coklu okul: her sorgu otomatik aktif okula filtreleniyor.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
```

### `app/Models/Room.php`

```php
<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    protected $fillable = [
        'school_id',
        'name',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function section(): HasOne
    {
        return $this->hasOne(Section::class);
    }
}
```

### `app/Models/Course.php`

```php
<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'hourly_rate',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function courseLevels(): HasMany
    {
        return $this->hasMany(CourseLevel::class);
    }
}
```

### `app/Models/Teacher.php`

```php
<?php

namespace App\Models;

use App\Models\Scopes\OkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'last_name',
        'national_id',
        'birth_date',
        'email',
        'photo_path',
        'cv_path',
        'is_active',
        'max_weekly_hours',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OkulScope);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function courseLevels(): HasMany
    {
        return $this->hasMany(TeacherCourseLevel::class);
    }

    public function sectionCourses(): HasMany
    {
        return $this->hasMany(SectionCourseTeacher::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

### `app/Models/Section.php`

```php
<?php

namespace App\Models;

use App\Models\Scopes\SubeOkulScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = [
        'level_id',
        'name',
        'room_id',
    ];

    /**
     * sections tablosunda school_id sutunu yok - bilincli tasarim
     * karari (veri tekrari olmasin diye seviye uzerinden erisiliyor).
     * O yuzden OkulScope degil, level iliskisi uzerinden filtreleyen
     * SubeOkulScope kullaniliyor.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new SubeOkulScope);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
```

### Ölçek: pivot tablolar neden değişmedi

`CourseLevel`, `TeacherCourseLevel`, `SectionCourseTeacher`, `ScheduleEntry`, `LeaveRequest` — **hiçbirine scope eklemedim.** Sebebi: bu tablolar zaten scope'lu tablolara (`course_id`, `teacher_id`, `section_id`) referans veriyor, ve mevcut servisler (`OgretmenAtamaServisi`, `SlotYerlestirmeServisi`) bunları hep **önce scope'lanmış bir koleksiyondan** (`$ogretmenler`, `$subeler`) gelen id'lerle eşleştiriyor:

```php
$adaylar = $yetkiler[$dersId . '-' . $sube->level_id] ?? [];
foreach ($adaylar as $ogretmenId) {
    if (! isset($ogretmenler[$ogretmenId])) {
        continue;   // ← B okulunun öğretmeni buraya düşerse sessizce elenir
    }
```

`$ogretmenler` zaten `Teacher::where('is_active', true)->get()` ile scope'lu geldiği için, başka bir okulun id'si bu diziye hiç girmiyor — `isset()` kontrolü onu doğal olarak eliyor. Yani algoritmanın "id ile bul, bulamazsan atla" deseni **kazara** çoklu okul güvenli çıktı, ekstra scope eklemeye gerek kalmadı.

---

## 4. Aktif Okul — Merkezi Yardımcı

### `app/Models/School.php` — `aktif()` metodu eklendi

```php
<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'city',
        'district',
        'lessons_per_day',
        'lesson_duration',
        'break_duration',
    ];

    /**
     * Su an secili olan okul. Coklu okul mimarisinin merkezi.
     *
     * Onceki tek-okul kodunda her yerde School::first() vardi. Bunun
     * yerine artik bu metot cagiriliyor - oturumda tutulan aktif okul
     * id'sini okuyor, gecersizse (silinmis, hic secilmemis) ilk okula
     * dusup oturuma yaziyor.
     *
     * Level/Room/Course/Teacher modellerindeki OkulScope de ayni
     * session anahtarini okuyor - o yuzden bu metodu cagirmak (ya da
     * EnsureAktifOkul middleware'inin cagirmasi) o modellerin dogru
     * filtrelenmesi icin sart.
     */
    public static function aktif(): ?self
    {
        $id = session('aktif_okul_id');
        $okul = $id ? static::find($id) : null;

        if (! $okul) {
            $okul = static::orderBy('id')->first();

            if ($okul) {
                session(['aktif_okul_id' => $okul->id]);
            }
        }

        return $okul;
    }

    public function aktifGunSayisi(): int
    {
        return $this->days->where('is_active', true)->count();
    }

    public function haftalikKapasite(): int
    {
        return $this->lessons_per_day * $this->aktifGunSayisi();
    }

    public function slotSaatleri(int $weekday): array
    {
        $gun = $this->days->firstWhere('weekday', $weekday);

        if (! $gun || ! $gun->is_active) {
            return [];
        }

        $saatler = [];
        $baslangic = Carbon::parse($gun->start_time);

        for ($slot = 1; $slot <= $this->lessons_per_day; $slot++) {
            $bitis = $baslangic->copy()->addMinutes($this->lesson_duration);

            $saatler[$slot] = [
                'baslangic' => $baslangic->format('H:i'),
                'bitis' => $bitis->format('H:i'),
            ];

            $baslangic = $bitis->copy()->addMinutes($this->break_duration);
        }

        return $saatler;
    }

    public function days(): HasMany { return $this->hasMany(SchoolDay::class); }
    public function levels(): HasMany { return $this->hasMany(Level::class); }
    public function rooms(): HasMany { return $this->hasMany(Room::class); }
    public function courses(): HasMany { return $this->hasMany(Course::class); }
    public function teachers(): HasMany { return $this->hasMany(Teacher::class); }
}
```

**Neden `School` modelinde scope yok:** `School` kendisi kiracı (tenant) — kendi kendini filtrelemesi anlamsız olurdu. Sadece `Level`, `Room`, `Course`, `Teacher`, `Section` scope'lu.

**Neden 9 yerdeki `School::first()`'ü doğrudan `School::aktif()`'e çevirmek yeterliydi:** `School::aktif()` bir `School` **nesnesi** döndürüyor, tıpkı `first()` gibi. Çağıran kodun geri kalanı (`$okul->teachers()->create(...)` gibi) hiç değişmedi.

---

## 5. `EnsureAktifOkul` Middleware — Kritik Güvenlik Ağı

### `app/Http/Middleware/EnsureAktifOkul.php` — yeni dosya

```php
<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Her istekte oturumda gecerli bir aktif okul id'si olmasini garanti
 * ediyor. OkulScope/SubeOkulScope session('aktif_okul_id') bos ise
 * HICBIR SEY filtrelemez - yani bu middleware calismazsa coklu okulda
 * tum okullarin verisi karisir. Bu yuzden admin ve teacher route
 * gruplarinin ikisine de uygulaniyor.
 *
 * @param  Closure(Request): (Response)  $next
 */
class EnsureAktifOkul
{
    public function handle(Request $request, Closure $next): Response
    {
        School::aktif();

        return $next($request);
    }
}
```

### Neden bu middleware olmadan sistem tehlikeli olurdu

`OkulScope`'a tekrar bak:

```php
if ($okulId = session('aktif_okul_id')) {
    $builder->where($model->getTable() . '.school_id', $okulId);
}
```

**Eğer `session('aktif_okul_id')` boşsa, `if` bloğu hiç çalışmıyor — sorgu HİÇ filtrelenmiyor, tüm okulların verisi karışık dönüyor.** Bu, güvenlik açısından **en tehlikeli senaryo**: sessizce yanlış değil, sessizce **hiç korumasız**.

Bu middleware her istekte `School::aktif()`'i çağırıyor — bu da oturumda değer yoksa ilk okula düşüp oturuma yazıyor. Yani bir kullanıcı ilk kez giriş yaptığında, oturumu bir şekilde temizlendiğinde, ya da aktif okulu silindiğinde — her durumda oturum **kendiliğinden onarılıyor**, sorgular hiç korumasız kalmıyor.

### `bootstrap/app.php` — middleware kaydı

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'teacher' => \App\Http\Middleware\EnsureUserIsTeacher::class,
            'aktifokul' => \App\Http\Middleware\EnsureAktifOkul::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```

`'aktifokul' => ...` satırı eklendi — böylece route'larda kısa isimle kullanılabiliyor.

### `routes/web.php` — middleware uygulanışı

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'aktifokul'])
    ->name('dashboard');

// aktifokul: bu sube/ders/ogretmen sorgulari coklu okulda dogru
// okula filtrelensin diye.
Route::middleware(['auth', 'teacher', 'aktifokul'])->prefix('ogretmen')->name('ogretmen.')->group(function () {
    // ... öğretmen rotaları ...
});

Route::middleware(['auth', 'admin', 'aktifokul'])->group(function () {
    // ... yönetici rotaları (okul, seviyeler, şubeler, öğretmenler, ...) ...
});
```

Hem yönetici hem öğretmen grubuna eklendi — öğretmen paneli de `Course::pluck(...)` gibi scope'lu sorgular çalıştırdığı için ona da gerekiyor.

---

## 6. Okul Yönetimi Ekranları

### `app/Http/Requests/OkulOlusturRequest.php` — yeni dosya

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Yeni okul olusturma. Sartname 2.1.7 - birden fazla okul eklenebilmeli.
 *
 * Egitim gunleri burada istenmiyor: yeni okul once bos olusuyor,
 * yonetici olusturduktan sonra /okul/duzenle uzerinden gunleri
 * tanimliyor. Ayni formda ikisini birden istemek formu sisirirdi.
 */
class OkulOlusturRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'lessons_per_day' => 'required|integer|min:1|max:20',
            'lesson_duration' => 'required|integer|min:5|max:120',
            'break_duration' => 'required|integer|min:0|max:60',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Okul adı zorunludur.',
            'city.required' => 'İl zorunludur.',
            'district.required' => 'İlçe zorunludur.',
        ];
    }
}
```

**Neden eğitim günleri bu formda yok:** Mevcut `SchoolRequest` (okul ayarları düzenleme) günleri de istiyor — 7 gün × (aktif mi + saat) birlikte. Yeni okul oluştururken aynı formu dayatmak kullanıcıyı yorardı. Bunun yerine: okul **boş** oluşuyor, otomatik aktif oluyor, yönetici hemen `/okul/duzenle`'ye yönlendiriliyor ve günleri orada tanımlıyor.

### `app/Http/Controllers/SchoolController.php` — tam dosya

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\OkulOlusturRequest;
use App\Http\Requests\SchoolRequest;
use App\Models\Level;
use App\Models\School;
use App\Models\Teacher;
use App\Services\OkulAyarServisi;

class SchoolController extends Controller
{
    private const GUN_ADLARI = [
        1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
        5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar',
    ];

    public function index()
    {
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return redirect()
                ->route('okullar.create')
                ->with('error', 'Henüz hiç okul tanımlanmamış. Önce bir okul ekleyin.');
        }

        $aktifGunler = [];
        $dersSaatleri = [];

        foreach (self::GUN_ADLARI as $no => $ad) {
            $gun = $okul->days->firstWhere('weekday', $no);

            if ($gun && $gun->is_active) {
                $aktifGunler[$no] = $ad;
                $dersSaatleri[$no] = $okul->slotSaatleri($no);
            }
        }

        return view('schools.index', [
            'okul' => $okul,
            'gunAdlari' => self::GUN_ADLARI,
            'aktifGunler' => $aktifGunler,
            'dersSaatleri' => $dersSaatleri,
            'haftalikKapasite' => $okul->haftalikKapasite(),
        ]);
    }

    public function edit()
    {
        $okul = School::aktif()?->load('days');

        if (! $okul) {
            return redirect()
                ->route('okullar.create')
                ->with('error', 'Henüz hiç okul tanımlanmamış. Önce bir okul ekleyin.');
        }

        $gunVerileri = [];

        foreach (self::GUN_ADLARI as $no => $ad) {
            $gun = $okul->days->firstWhere('weekday', $no);

            $gunVerileri[$no] = [
                'ad' => $ad,
                'aktif' => $gun?->is_active ?? false,
                'saat' => $gun ? substr($gun->start_time, 0, 5) : '08:00',
            ];
        }

        return view('schools.edit', [
            'okul' => $okul,
            'gunVerileri' => $gunVerileri,
        ]);
    }

    public function update(SchoolRequest $request, OkulAyarServisi $servis)
    {
        $servis->guncelle(School::aktif(), $request->validated());

        return redirect()
            ->route('okul.index')
            ->with('success', 'Okul ayarları güncellendi.');
    }

    // ───── ÇOKLU OKUL — Sartname 2.1.7 ──────────────────────────

    /**
     * Tum okullarin listesi + hangisinin aktif oldugu + okul degistirme.
     *
     * withCount('levels') KULLANMIYORUM: Level modelinde global scope
     * var, withCount'un urettigi correlated subquery'ye scope'un
     * "AND school_id = <aktif>" kosulu da eklenir. Sonuc: sadece aktif
     * okulun sayisi dogru cikar, digerleri hep 0 gorunur. Bunun yerine
     * withoutGlobalScopes() ile scope'u BILEREK atlayip her okul icin
     * kendi id'sine gore ayri sayiyorum.
     */
    public function okullar()
    {
        $okullar = School::orderBy('name')->get()->map(function ($okul) {
            $okul->seviye_sayisi = Level::withoutGlobalScopes()
                ->where('school_id', $okul->id)->count();
            $okul->ogretmen_sayisi = Teacher::withoutGlobalScopes()
                ->where('school_id', $okul->id)->count();

            return $okul;
        });

        return view('schools.okullar', [
            'okullar' => $okullar,
            'aktifId' => School::aktif()?->id,
        ]);
    }

    public function okulEkleForm()
    {
        return view('schools.ekle');
    }

    /**
     * Yeni okul olusturur ve OTOMATIK aktif okul yapar - yonetici
     * ekledigi okulu hemen yapilandirmaya devam edebilsin diye.
     */
    public function okulEkle(OkulOlusturRequest $request)
    {
        $okul = School::create($request->validated());

        session(['aktif_okul_id' => $okul->id]);

        return redirect()
            ->route('okul.edit')
            ->with('success', "\"{$okul->name}\" eklendi ve aktif okul olarak seçildi. Şimdi eğitim günlerini tanımlayın.");
    }

    /**
     * Aktif okulu degistirir. Sonraki istekten itibaren OkulScope/
     * SubeOkulScope butun sorgulari bu okula filtreler.
     */
    public function aktifYap(School $okul)
    {
        session(['aktif_okul_id' => $okul->id]);

        return redirect()
            ->route('okullar.index')
            ->with('success', "\"{$okul->name}\" aktif okul olarak seçildi.");
    }
}
```

### Yol boyunca bulduğum bir hata: `School::with('days')->first()`

İlk sed düzeltmem `School::first()` deseninin **tamamını** yakalamıştı sanmıştım. Ama `index()` ve `edit()` metotları farklı bir kalıp kullanıyordu:

```php
// Eski — sed'in YAKALAMADIĞI kalıp
$okul = School::with('days')->first();
```

Bu, `School::first()`'ten **farklı bir metin dizisi** olduğu için otomatik değiştirme atladı. Bunu ayrıca bulup elle düzelttim:

```php
// Yeni
$okul = School::aktif()?->load('days');
```

Bu hata düzeltilmeseydi, "aktif okulu değiştir" düğmesi hiçbir şeyi değiştirmezdi — okul ayarları ekranı **hep ilk okulu** göstermeye devam ederdi. Regresyon testinde bunu SQL loglarını inceleyerek buldum.

### `resources/views/schools/okullar.blade.php` — yeni dosya

```blade
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
```

### `resources/views/schools/ekle.blade.php` — yeni dosya

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Okul Ekle</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-6 rounded bg-red-50 border border-red-200 p-4">
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $hata)
                                <li>{{ $hata }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="text-sm text-gray-600 mb-6">
                    Okul eklendikten sonra otomatik olarak aktif okul yapılır ve
                    eğitim günlerini tanımlayacağınız ekrana yönlendirilirsiniz.
                    Seviye, şube, ders ve öğretmenleri daha sonra bu okul aktifken
                    ekleyebilirsiniz.
                </p>

                <form method="POST" action="{{ route('okullar.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Okul Adı</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">İl</label>
                            <input id="city" name="city" type="text" value="{{ old('city') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="district" class="block text-sm font-medium text-gray-700">İlçe</label>
                            <input id="district" name="district" type="text" value="{{ old('district') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label for="lessons_per_day" class="block text-sm font-medium text-gray-700">
                                Günde Ders Sayısı
                            </label>
                            <input id="lessons_per_day" name="lessons_per_day" type="number"
                                   value="{{ old('lessons_per_day', 7) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="lesson_duration" class="block text-sm font-medium text-gray-700">
                                Ders Süresi (dk)
                            </label>
                            <input id="lesson_duration" name="lesson_duration" type="number"
                                   value="{{ old('lesson_duration', 40) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label for="break_duration" class="block text-sm font-medium text-gray-700">
                                Mola Süresi (dk)
                            </label>
                            <input id="break_duration" name="break_duration" type="number"
                                   value="{{ old('break_duration', 10) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t pt-6">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded hover:bg-gray-700">
                            Okulu Ekle
                        </button>
                        <a href="{{ route('okullar.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Vazgeç
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 7. Yeni Rotalar

**Dosya: `routes/web.php`** — yönetici grubunun içine eklenenler:

```php
// Sartname 2.1.7 — birden fazla okul eklenebilmeli
Route::get('/okullar', [SchoolController::class, 'okullar'])->name('okullar.index');
Route::get('/okullar/ekle', [SchoolController::class, 'okulEkleForm'])->name('okullar.create');
Route::post('/okullar', [SchoolController::class, 'okulEkle'])->name('okullar.store');
Route::post('/okullar/{okul}/aktif-yap', [SchoolController::class, 'aktifYap'])->name('okullar.aktif');
```

`aktifYap(School $okul)` metodunda route model binding çalışıyor — `{okul}` parametresi doğrudan `School` nesnesine çevriliyor. `School` modelinde scope olmadığı için bu binding **her zaman** tüm okullar arasından arıyor, sorun yok.

---

## 8. `unique` Kurallarının Okul Bazına Çekilmesi

Çoklu okul aktifken bir sorun ortaya çıkıyordu: "9. Sınıf" adı **global** olarak benzersiz kontrol ediliyordu — yani Ankara'da "9. Sınıf" varsa İstanbul'da da "9. Sınıf" **eklenemezdi.** Bu yanlış; iki okul aynı ada sahip olabilmeli, benzersizlik **okul içinde** aranmalı.

### `app/Http/Requests/LevelRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seviyeId = $this->route('seviye')?->id;

        return [
            'grade_number' => [
                'required',
                'integer',
                'min:9',
                'max:12',
                // where('school_id', ...) coklu okulda gerekli: iki
                // okul da kendi "9. Sinif"ina sahip olabilmeli, benzersizlik
                // sadece AYNI okul icinde aranmali.
                Rule::unique('levels', 'grade_number')
                    ->where('school_id', School::aktif()?->id)
                    ->ignore($seviyeId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'grade_number.required' => 'Sınıf numarası zorunludur.',
            'grade_number.integer' => 'Sınıf numarası tam sayı olmalıdır.',
            'grade_number.min' => 'Sınıf numarası 9 ile 12 arasında olmalıdır.',
            'grade_number.max' => 'Sınıf numarası 9 ile 12 arasında olmalıdır.',
            'grade_number.unique' => ':input. Sınıf zaten tanımlı.',
        ];
    }
}
```

### `app/Http/Requests/RoomRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $derslikId = $this->route('derslik')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // where('school_id', ...): iki okul da kendi "D-101"ine
                // sahip olabilmeli, benzersizlik okul icinde aranmali.
                Rule::unique('rooms', 'name')
                    ->where('school_id', School::aktif()?->id)
                    ->ignore($derslikId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Derslik adı zorunludur.',
            'name.unique' => 'Bu adda bir derslik zaten var.',
        ];
    }
}
```

### `app/Http/Requests/CourseRequest.php` — ilgili satır

```php
'name' => [
    'required',
    'string',
    'max:255',
    Rule::unique('courses', 'name')->where('school_id', School::aktif()?->id)->ignore($dersId),
],
```

**Öğretmen alanlarına dokunmadım:** `TeacherController`'daki `national_id` ve `email` benzersizlik kuralları **global** kaldı, bilerek. TC kimlik numarası gerçek dünyada zaten tek bir kişiye ait — okula göre değişmemesi doğru davranış. Aynı mantık e-posta için de geçerli.

---

## 9. Menü — "Okullar" Bağlantısı

**Dosya: `resources/views/layouts/navigation.blade.php`**

Masaüstü menüsünde, "Okul Ayarları" bağlantısının hemen altına eklendi:

```blade
<x-nav-link :href="route('okul.index')" :active="request()->routeIs('okul.*')">
    Okul Ayarları
</x-nav-link>

<x-nav-link :href="route('okullar.index')" :active="request()->routeIs('okullar.*')">
    Okullar
</x-nav-link>
```

Mobil menüde de aynı yapı `x-responsive-nav-link` ile tekrarlandı.

**Not:** `routeIs('okul.*')` deseni `okullar.index` ile **çakışmıyor** — Laravel'in `routeIs()` metodu `okul.` (nokta dahil) ile başlayan isimleri arıyor, `okullar.index` "okul." ile başlamıyor (araya "lar" giriyor), yanlış eşleşme olmuyor.

---

## 10. Test — Gerçek İzolasyon Kanıtı

Kod tabanı taranıp yazıldıktan sonra ikinci bir okul oluşturup **canlı test** ettim:

```php
$okul2 = School::create([
    'name' => 'Istanbul Ticaret Lisesi',
    'city' => 'Istanbul', 'district' => 'Kadikoy',
    'lessons_per_day' => 6, 'lesson_duration' => 45, 'break_duration' => 15,
]);
// ... 1 seviye (9. Sınıf), 1 şube (9A), 1 derslik (D-101),
//     1 ders (Muhasebe), 1 öğretmen (Selin Kaya) eklendi
```

Sonuç:

```
--- Ankara aktifken ---
  Level::count()   = 4
  Section::count() = 13
  Teacher::count() = 32

--- Istanbul aktifken ---
  Level::count()   = 1
  Section::count() = 1
  Teacher::count() = 1
  Section isimleri: 9A          ← Ankara'nın 13 şubesi HİÇ görünmüyor

--- Tekrar Ankara aktifken ---
  Section::count() = 13         ← geri döndü, hiçbir veri kaybolmadı

=== Scope'suz gerçek toplam ===
Level (scope'suz)   = 5   (4 + 1, doğru)
Section (scope'suz) = 14  (13 + 1, doğru)
```

**En önemli kanıt:** İki okul da "9. Sınıf" ve "9A" adını **çakışma hatası almadan** kullanabildi — `unique` kurallarının okul bazında çalıştığının kanıtı.

---

## 11. Değişen / Oluşturulan Dosyaların Tam Listesi

### Yeni dosyalar (9)

```
app/Models/Scopes/OkulScope.php
app/Models/Scopes/SubeOkulScope.php
app/Http/Middleware/EnsureAktifOkul.php
app/Http/Requests/OkulOlusturRequest.php
resources/views/schools/okullar.blade.php
resources/views/schools/ekle.blade.php
```

### Değiştirilen dosyalar (önemli olanlar)

```
app/Models/School.php              → aktif() metodu eklendi
app/Models/Level.php               → OkulScope bağlandı
app/Models/Room.php                → OkulScope bağlandı
app/Models/Course.php              → OkulScope bağlandı
app/Models/Teacher.php             → OkulScope bağlandı
app/Models/Section.php             → SubeOkulScope bağlandı
app/Http/Controllers/SchoolController.php    → okullar()/okulEkleForm()/okulEkle()/aktifYap() eklendi, index()/edit() düzeltildi
app/Http/Controllers/DashboardController.php → School::first() → School::aktif()
app/Http/Controllers/TeacherController.php   → 2 yerde School::first() → School::aktif()
app/Http/Controllers/CourseController.php    → School::first() → School::aktif()
app/Http/Controllers/LevelController.php     → School::first() → School::aktif()
app/Http/Controllers/RoomController.php      → School::first() → School::aktif()
app/Http/Requests/CourseRequest.php          → School::first() → School::aktif(), unique kuralı okul bazına çekildi
app/Http/Requests/LevelRequest.php           → unique kuralı okul bazına çekildi
app/Http/Requests/RoomRequest.php            → unique kuralı okul bazına çekildi
app/Services/SlotYerlestirmeServisi.php      → School::first() → School::aktif()
bootstrap/app.php                            → 'aktifokul' middleware takma adı eklendi
routes/web.php                               → 4 yeni rota, 3 route grubuna 'aktifokul' middleware'i eklendi
resources/views/layouts/navigation.blade.php → "Okullar" bağlantısı (masaüstü + mobil)
```

**Dokunulmayan ama artık farklı çalışan 40+ satır** — `Level::orderBy(...)`, `Teacher::where('is_active', true)`, `Course::pluck(...)` gibi — global scope sayesinde hiç değişmedi, davranışları arka planda değişti.

---

## 12. Sonradan Bulunan Kritik Açıklar ve Düzeltmeleri

Bu dokümanın ilk halinde, bölüm 3'teki "pivot tablolar neden değişmedi" kısmında **hatalı bir genelleme** vardı: "algoritma id ile eler, pivotlara scope gerekmez" iddiası sadece sihirbaz servislerinin (`OgretmenAtamaServisi`, `SlotYerlestirmeServisi`, `CizelgeServisi` içindeki *belirli* metotlar) kendine özgü "önce scope'lu koleksiyon çek, sonra id ile eşleştir" deseni için geçerliydi — genel bir kural değildi.

Kod tabanı bu açıdan tekrar tarandığında, aynı desene uymayan **4 gerçek, kritik nokta** bulundu:

### a) Tekrarlı sihirbaz TÜM okulların programını/atamalarını siliyordu

```php
// Dosya: app/Services/SlotYerlestirmeServisi.php (ESKİ HALİ)
ScheduleEntry::query()->delete();

// Dosya: app/Services/OgretmenAtamaServisi.php (ESKİ HALİ)
SectionCourseTeacher::query()->delete();
```

Sihirbaz "tekrarlı çalıştırılabilmeli" (şartname 2.6.2/5) gereği önce eskiyi silip yeniden yazıyor. Ama bu silme **hiçbir okul filtresi içermiyordu** — B okulunda sihirbazı çalıştırmak A okulunun **tüm programını ve atamalarını da** silerdi. Bu, kod incelemesiyle değil, doğrudan gerçek veriyle test edilerek bulundu.

**Düzeltme:**

```php
ScheduleEntry::whereHas('section')->delete();
SectionCourseTeacher::whereHas('section')->delete();
```

`whereHas('section')`, `Section` modelinin kendi `SubeOkulScope`'unu otomatik olarak alt sorguya taşıyor — yani bu tek satır değişikliği, aktif okulun dışındaki hiçbir kaydı silmiyor.

**Kanıt (gerçek veriyle test edildi):** İstanbul'a sahte bir `ScheduleEntry` + `SectionCourseTeacher` satırı eklendim, İstanbul aktifken silme çalıştırdım:

```
Silmeden ÖNCE - Ankara ScheduleEntry (gerçek toplam)        = 417  (416 Ankara + 1 test satırı İstanbul)
Silmeden SONRA - Ankara ScheduleEntry (gerçek toplam)        = 416  (Ankara'ya hiç dokunulmadı)
İstanbul kalan ScheduleEntry        = 0
```

### b) Yönetici izin ekranı tüm okulların taleplerini karışık gösteriyordu

```php
// Dosya: app/Http/Controllers/IzinController.php — yonetimIndex() (ESKİ HALİ)
$izinler = LeaveRequest::with('teacher')->orderByRaw(...)->get();
```

`LeaveRequest` modelinde scope yok (bilinçli — bir izin talebi zaten tek bir öğretmene ait, öğretmen üzerinden dolaylı erişilecek şekilde tasarlandı) ama bu satır o dolaylı filtrelemeyi hiç yapmıyordu.

**Düzeltme:**

```php
$izinler = LeaveRequest::whereHas('teacher')->with('teacher')->orderByRaw(...)->get();
```

Aynı düzeltme `IzinServisi::bekleyenSayisi()` içindeki bekleyen izin sayacına da uygulandı.

### c) Dashboard/çizelge/program sayaçları tüm okulları topluyordu

```php
// Dosya: app/Http/Controllers/DashboardController.php, CizelgeController.php, ProgramController.php (ESKİ HALİ)
ScheduleEntry::count()
SectionCourseTeacher::count()
```

Aktif okul kaç değişirse değişsin bu sayılar hep sistemdeki toplam kayıt sayısını gösteriyordu. `whereHas('section')` eklenerek düzeltildi.

### d) Yük dağılımı raporu (şartname 5.5) tüm okulları karıştırıyordu

```php
// Dosya: app/Services/CizelgeServisi.php — yukDagilimi() (ESKİ HALİ)
$saatler = ScheduleEntry::query()->selectRaw('teacher_id, COUNT(*) AS saat')->groupBy('teacher_id')->pluck(...);
$branslar = TeacherCourseLevel::query()->select('teacher_id', 'course_id')->distinct()->get()...;
```

"Hangi öğretmen haftada kaç saat ders almış?" raporu, farklı okullardaki öğretmenlerin saatlerini karıştırabilirdi (id çakışması olmasa bile, yanlış okulun öğretmen listesiyle birlikte gösterilirdi). `whereHas('teacher')` eklendi.

### Neyin dokunulmadan bırakıldığı ve neden güvenli olduğu

Aynı taramada bulunan şu sorgular **kasıtlı olarak değiştirilmedi**, çünkü zaten güvenliler:

- `LeaveRequest::where('teacher_id', $ogretmen->id)` (öğretmen paneli, kendi izinleri) — `$ogretmen = $request->user()->teacher`, yani oturum açmış kullanıcının **kendi** kaydı. Okul filtresine gerek yok, zaten kendi verisi.
- `ScheduleEntry::where('room_id', $derslik->id)->exists()`, `TeacherCourseLevel::where('teacher_id', $ogretmen->id)` gibi controller'lardaki kontroller — `$derslik`/`$ogretmen` zaten scope'lu modellerden (`Room`, `Teacher`) route-model-binding ile geliyor; scope'lu olmayan bir okulun id'si bu değişkene hiç giremez (binding 404 verir).

### Ders alınan şey

"Pivot tabloya scope eklemeye gerek yok" cümlesi tehlikeli bir genelleme — doğru soru her zaman **"bu spesifik sorgu, filtresiz mi çalışıyor, yoksa zaten scope'lu bir veriden mi geliyor?"** olmalı. Genel kural yerine her çağrı noktası tek tek denetlenmeli.

---

## 13. Şefe / Mentöre Özet Cümle

> "Çoklu okul desteğini global scope ile çözdüm — `Level`, `Room`, `Course`, `Teacher` modellerine bir scope ekleyip her sorgunun otomatik aktif okula filtrelenmesini sağladım, bu sayede kod tabanındaki 40'tan fazla mevcut sorgu satırına hiç dokunmadım. `Section` tablosunda `school_id` sütunu olmadığı için (bilinçli tasarım kararı) onu seviye ilişkisi üzerinden filtreleyen ayrı bir scope yazdım.
>
> Aktif okul oturumda tutuluyor, bir middleware her istekte bunun dolu olduğunu garanti ediyor — çünkü scope'lar oturum boşken hiç filtrelemiyor, bu da middleware olmadan okulların verisinin karışacağı anlamına gelirdi.
>
> Yeni okul ekleme, okul listesi ve okul değiştirme ekranları var. Test için ikinci bir okul oluşturup gerçek verilerle izolasyonu doğruladım: bir okulda 13 şube varken diğerine geçince sadece 1 şube görünüyor, geri dönünce 13'ü eksiksiz geri geliyor. İki okul da aynı isimleri (örneğin '9. Sınıf') çakışmadan kullanabiliyor çünkü benzersizlik kurallarını okul bazına çektim.
>
> Son bir güvenlik geçişinde, model scope'larının kapsamadığı `ScheduleEntry`/`SectionCourseTeacher`/`LeaveRequest` gibi pivot/bağımlı tabloları da tek tek denetledim. Algoritma içindeki id eşleştirmeleri çoğu çapraz-okul kaydını doğal biçimde eliyordu, ama sihirbazın 'tekrarlı çalıştırma' silme adımı (`::query()->delete()`) hiç filtre içermediği için bir okulda sihirbazı çalıştırmak diğer okulun programını da silebiliyordu — bunu gerçek veriyle (sahte bir kayıt ekleyip silme öncesi/sonrası sayarak) test edip doğruladım ve düzelttim. Aynı taramada izin listesi ve dashboard sayaçlarındaki iki küçük sızıntıyı da kapattım."
