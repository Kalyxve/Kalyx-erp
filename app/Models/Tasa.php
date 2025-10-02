<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tasa extends Model
{
    protected $table = 'tasas';

    protected $fillable = [
        'valor',
        'fuente',        // 'bcv' | 'manual'
        'vigente_desde', // date
    ];

    protected $casts = [
        'valor'         => 'decimal:4',
        'vigente_desde' => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    /** Última tasa (manual tiene prioridad si existe en fecha igual/mayor) */
    public static function vigente(): ?self
    {
        return static::orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->first();
    }

    /** Scope por fuente */
    public function scopeFuente($q, string $fuente)
    {
        return $q->where('fuente', $fuente);
    }
}
