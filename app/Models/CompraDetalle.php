<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraDetalle extends Model
{
    protected $table = 'compra_detalles';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'precio_unitario_usd',
        'precio_unitario_bs',
        'subtotal_usd',
        'subtotal_bs',
    ];

    protected $casts = [
        'cantidad'            => 'decimal:2',
        'precio_unitario_usd' => 'decimal:2',
        'precio_unitario_bs'  => 'decimal:2',
        'subtotal_usd'        => 'decimal:2',
        'subtotal_bs'         => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Producto::class, 'producto_id');
    }
}
