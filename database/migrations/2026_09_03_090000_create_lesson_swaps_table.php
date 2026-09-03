<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ders takasi - bir ogretmenin izni yuzunden iki dersin BELIRLI BIR
 * TARIHTE yer degistirmesi.
 *
 * Neden ayri bir tablo, neden schedule_entries guncellenmiyor?
 * schedule_entries HAFTALIK BIR SABLON - tarihsiz, "her Persembe
 * 3. ders" demek. Izin ise TARIHLI bir olay. Sablondaki iki satirin
 * weekday/slot_no'sunu takaslasaydim degisiklik HER HAFTA gecerli
 * olurdu; oysa izin sadece o gune ait. Bu yuzden takas da tarihli bir
 * kayit ve sablon hic bozulmuyor - ikisi goruntuleme katmaninda
 * birlesiyor (bkz. CizelgeServisi).
 *
 * Bunun kazandirdiklari:
 * - takas geri alininca program kendiliginden eski haline donuyor
 * - "aslinda bu ders ne zamandi" bilgisi hic kaybolmuyor
 * - sadece o tarih etkileniyor, sonraki haftalar degil
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_swaps', function (Blueprint $table) {
            $table->id();

            // Takasin sebebi. Izin silinirse takasin varlik sebebi de
            // ortadan kalkiyor - cascade.
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();

            // Takasin gecerli oldugu TEK gun.
            $table->date('swap_date');

            // Yer degistiren iki ders. entry_a: izinli olan ders,
            // entry_b: onunla yer degisen ders.
            //
            // cascadeOnDelete BURADA KRITIK: program sihirbazinin
            // 2. adimi butun schedule_entries kayitlarini silip yeniden
            // uretiyor. O silme sirasinda bu takaslar da gitmezse,
            // artik var olmayan derslere isaret eden oksuz kayitlar
            // kalirdi.
            $table->foreignId('entry_a_id')->constrained('schedule_entries')->cascadeOnDelete();
            $table->foreignId('entry_b_id')->constrained('schedule_entries')->cascadeOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Ayni gunde ayni ders birden fazla takasa giremez.
            //
            // Not: bu iki kisit tek basina yetmiyor - bir ders bir
            // takasta "a", baska bir takasta "b" olarak gecebilir ve
            // veritabani bunu yakalamaz. Tam kontrol TakasServisi'nde
            // transaction icinde yapiliyor; bu kisitlar en sik hatayi
            // (ayni tarafta tekrar) veritabani seviyesinde kesiyor.
            $table->unique(['swap_date', 'entry_a_id']);
            $table->unique(['swap_date', 'entry_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_swaps');
    }
};
