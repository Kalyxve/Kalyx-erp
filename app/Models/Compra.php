<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'proveedor_id',
        'fecha',
        'numero',
        'total_usd',
        'total_bs',
        'estado', // registrada | anulada
    ];

    protected $casts = [
        'total_usd'  => 'decimal:2',
        'total_bs'   => 'decimal:2',
        'fecha'      => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
    }
}
