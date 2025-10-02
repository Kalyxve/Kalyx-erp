<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaDetalle extends Model
{
    protected $table = 'factura_detalles';

    protected $fillable = [
        'factura_id',
        'producto_id',
        'cantidad',             // DECIMAL(12,3) soporta KG/LT
        'precio_unitario_usd',
        'precio_unitario_bs',
        'subtotal_usd',
        'subtotal_bs',
        'tasa_usd_item',
    ];

    protected $casts = [
        'cantidad'            => 'decimal:3',
        'precio_unitario_usd' => 'decimal:4',
        'precio_unitario_bs'  => 'decimal:2',
        'subtotal_usd'        => 'decimal:4',
        'subtotal_bs'         => 'decimal:2',
        'tasa_usd_item'       => 'decimal:4',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}
