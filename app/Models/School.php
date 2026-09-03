<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

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
        // ─────────────────────────────────────────────────────────
        // Okula bagli yonetici: aktif okul HER ZAMAN kendi okulu.
        //
        // Oturumdaki deger bilerek DIKKATE ALINMIYOR. Yetkilendirmenin
        // butun agirligi burada: OkulScope, SubeOkulScope ve okulu
        // "aktif okul" uzerinden bulan her sorgu bu metodun yazdigi
        // session degerinden besleniyor. Kisitlamayi tek tek
        // controller'lara serpistirmek yerine buraya koyunca, yeni
        // yazilan bir ekran bile kendiliginden dogru filtreleniyor -
        // birini eklemeyi unutma riski kalmiyor.
        //
        // auth() CLI'da (tinker, migration, seeder) null doner; o
        // durumda asagidaki eski davranis gecerli kalir.
        // ─────────────────────────────────────────────────────────
        /** @var \App\Models\User|null $kullanici */
            $kullanici = Auth::user();

        if ($kullanici?->okulaBagliYoneticiMi()) {
            // school_id null olabilir (okulu silinmis yonetici) - find(null)
            // null doner ve asagidaki "hicbir okul" dalina duseriz.
            $kendiOkulu = $kullanici->school_id ? static::find($kullanici->school_id) : null;

            if ($kendiOkulu) {
                // Oturumu da hizalliyoruz: scope'lar dogrudan session'i
                // okuyor, bu metottan gecmeyen bir sorgu olursa o da
                // dogru okulu gorsun.
                session(['aktif_okul_id' => $kendiOkulu->id]);

                return $kendiOkulu;
            }

            // Okulu yok ya da silinmis: HICBIR okula dusurmuyoruz.
            // Asagidaki "ilk okula dus" davranisi burada yanlis olurdu -
            // yetkisi olmayan bir okulun verisi acilirdi.
            //
            // Oturumdaki eski deger de siliniyor: kalsaydi OkulScope onu
            // okuyup kullaniciyi BASKA bir okula filtreleyebilirdi.
            // Bosalttiktan sonra scope hic filtrelemez (yani her seyi
            // gosterir) - bu daha da kotu olurdu, o yuzden isin geri
            // kalanini EnsureAktifOkul middleware'i kesiyor: aktif okul
            // yoksa istek 403 ile duruyor, hicbir sorgu calismiyor.
            session()->forget('aktif_okul_id');

            return null;
        }

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

    /**
     * Eğitim verilen gün sayısı.
     */
    public function aktifGunSayisi(): int
    {
        // İlişki koleksiyonu üzerinden: bir kez yükleniyor,
        // tekrar çağrılarda yeniden sorgu atılmıyor.
        return $this->days->where('is_active', true)->count();
    }

    /**
     * Bir şubenin (ve bir öğretmenin) haftada girebileceği azami ders saati.
     *
     * Üç yerde kullanılıyor: öğretmen max_weekly_hours üst sınırı,
     * ders saati doğrulaması, program sihirbazının slot sayısı.
     * Sabit yazılmıyor — okul ayarı değişince hepsi birlikte değişsin diye.
     */
    public function haftalikKapasite(): int
    {
        return $this->lessons_per_day * $this->aktifGunSayisi();
    }

    /**
     * Bir günün slot numaralarını gerçek saatlere çevirir.
     * Şartname 2.1.8 — ders saatleri programı.
     *
     * Saatler veritabanında saklanmıyor; ders süresi değişince
     * bütün program kendiliğinden kaysın diye buradan hesaplanıyor.
     */
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

    public function days(): HasMany
    {
        return $this->hasMany(SchoolDay::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
