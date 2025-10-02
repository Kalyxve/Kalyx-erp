<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $fillable = ['nombre', 'slug', 'descripcion', 'activo'];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->nombre);
        });
        static::updating(function ($m) {
            if ($m->isDirty('nombre')) $m->slug = Str::slug($m->nombre);
        });
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
