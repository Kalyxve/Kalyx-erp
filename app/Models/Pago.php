<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'factura_id',
        'metodo',      // efectivo_usd, efectivo_bs, zelle, pmovil, transferencia, pos, loteria, etc.
        'monto_usd',
        'monto_bs',
        'tasa_usd',
        'referencia',
        'fecha_pago',
        'nota',
        'extra',       // JSON: { voucher, banco, terminal, lote, ult4, titular, porcentaje_descuento, ... }
    ];

    protected $casts = [
        'monto_usd'  => 'decimal:4',
        'monto_bs'   => 'decimal:2',
        'tasa_usd'   => 'decimal:4',
        'fecha_pago' => 'date',
        'extra'      => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    /** Equivalente en USD (helper) */
    public function usdEquivalent(?float $fallbackTasa = null): float
    {
        $usd = (float)($this->monto_usd ?? 0);
        $bs  = (float)($this->monto_bs  ?? 0);
        $t   = (float)($this->tasa_usd ?? $fallbackTasa ?? 0);
        return $usd + ($t > 0 ? ($bs / $t) : 0.0);
    }
}
