<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'rif',
        'direccion',
        'telefono',
        'email',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones de ejemplo si las necesitas luego:
    // public function facturas()
    // {
    //     return $this->hasMany(Factura::class);
    // }
}
