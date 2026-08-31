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
