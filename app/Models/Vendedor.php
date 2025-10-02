<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    protected $table = 'vendedores';

    protected $fillable = [
        'nombre',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Scope: solo activos */
    public function scopeActivos($q)
    {
        return $q->where('activo', 1);
    }

    /** Rel: facturas realizadas por este vendedor */
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'vendedor_id');
    }

    /** Compat: si en alguna parte se usa ->name, devolvemos nombre */
    public function getNameAttribute()
    {
        return $this->nombre;
    }
}
