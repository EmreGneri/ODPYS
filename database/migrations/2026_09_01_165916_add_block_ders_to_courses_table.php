<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Sartname 4/5: "Sinifta bir gun icinde arka arkaya ayni ders
            // blok olarak verilebilir (opsiyonel kural)." Bu, ders bazinda
            // acilip kapatilabilen bir tercih - varsayilan kapali (mevcut
            // davranis: haftaya yayilma).
            $table->boolean('block_ders')->default(false)->after('hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('block_ders');
        });
    }
};
