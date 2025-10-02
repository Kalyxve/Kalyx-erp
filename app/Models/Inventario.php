<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    // ⬅️  AQUÍ EL ARREGLO: usa la tabla que tu migración creó
    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'producto_id',
        'tipo',              // 'entrada' | 'salida' | 'ajuste'
        'motivo',            // 'compra' | 'venta' | 'devolucion' | 'ajuste'
        'cantidad',
        'costo_unitario_usd',
        'costo_unitario_bs',
        'tasa_usd',
        'factura_id',
        'proveedor_id',
        'detalle',
    ];

    protected $casts = [
        'cantidad'           => 'integer',
        'costo_unitario_usd' => 'decimal:4',
        'costo_unitario_bs'  => 'decimal:2',
        'tasa_usd'           => 'decimal:4',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
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
