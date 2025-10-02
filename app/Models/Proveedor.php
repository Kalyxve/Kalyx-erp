<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social',
        'rif',
        'direccion',
        'telefono',
        'email',
        'activo',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }
}
