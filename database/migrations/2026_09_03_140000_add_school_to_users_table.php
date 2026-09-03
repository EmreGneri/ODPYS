<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yonetici rolunu ikiye boler:
 *
 *   superadmin -> butun okullari gorur, okul ekleyebilir, yonetici
 *                 atayabilir. school_id NULL.
 *   admin      -> SADECE school_id'sindeki okula erisir.
 *   teacher    -> degismedi; okulunu kendi ogretmen kaydindan aliyor.
 *
 * Neden users.school_id (tek okul), pivot tablo degil?
 * Pivot tablo, okul kapsami olmayan bir tablo daha demek olurdu -
 * bu projede tam olarak o desen (global scope'u olmayan pivot) daha
 * once dort ayri veri sizintisi hatasina yol acmisti. Tek sutun hem
 * daha az riskli hem sartnamedeki "okul bazli yonetici" ihtiyacini
 * karsiliyor. Bir yonetici birden fazla okul yonetecekse ileride
 * pivota gecilebilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable: superadmin ve ogretmenlerde bos kaliyor.
            // nullOnDelete: okul silinirse yonetici hesabi silinmesin,
            // sadece bagi kopsun (sonra superadmin yeniden atar).
            $table->foreignId('school_id')->nullable()->after('role')
                ->constrained()->nullOnDelete();
        });

        // Mevcut yoneticiler superadmin oluyor. Sistemde su an okul
        // bazli kisitlama yoktu, yani bugunku adminler fiilen zaten
        // butun okullara erisiyordu - davranislarini korumak icin
        // dogru karsilik superadmin.
        DB::table('users')->where('role', 'admin')->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
