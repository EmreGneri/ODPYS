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
