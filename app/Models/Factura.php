<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'cliente_id',
        'vendedor_id',
        'tipo_documento',   // 'venta' | 'pago_directo'
        'estado',           // 'borrador','pendiente','pagada'
        'tasa_usd',
        'total_usd',
        'total_bs',
        'saldo_usd',
        'saldo_bs',
        'fecha_emision',
        'fecha_vencimiento',
        'nota',
    ];

    protected $casts = [
        'tasa_usd'          => 'decimal:4',
        'total_usd'         => 'decimal:4',
        'total_bs'          => 'decimal:2',
        'saldo_usd'         => 'decimal:4',
        'saldo_bs'          => 'decimal:2',
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // ===== Relaciones =====
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    // ===== Helpers =====
    public function isVenta(): bool
    {
        return $this->tipo_documento === 'venta';
    }

    public function isPagoDirecto(): bool
    {
        return $this->tipo_documento === 'pago_directo';
    }
}
