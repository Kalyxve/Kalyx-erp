<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombre',
        'unidad',            // UND | KG | LT | PACK ...
        'precio_usd_base',
        'precio_bs_base',
        'tasa_usd_registro',
        'stock',
        'activo',
    ];

    protected $casts = [
        'precio_usd_base'   => 'decimal:4',
        'precio_bs_base'    => 'decimal:2',
        'tasa_usd_registro' => 'decimal:4',
        'stock'             => 'decimal:3', // por si manejas stock con decimales (KG/LT)
        'activo'            => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(\App\Models\Categoria::class);
    }


    protected static function booted()
    {
        static::creating(function ($m) {
            // Código correlativo por categoría: {PREF}-{NNN}
            // PREF = primeras 3 letras del slug de la categoría, en mayúsculas.
            if (empty($m->codigo)) {
                $cat = $m->categoria()->first();
                $prefix = 'PRD';
                if ($cat && !empty($cat->slug)) {
                    $prefix = strtoupper(substr($cat->slug, 0, 3)); // pan, far, etc.
                }

                $lastCode = static::where('codigo', 'like', $prefix . '-%')
                    ->orderByDesc('id')
                    ->value('codigo');

                $seq = 0;
                if ($lastCode && preg_match('/-(\d+)$/', $lastCode, $mm)) {
                    $seq = (int) $mm[1];
                }
                $m->codigo = sprintf('%s-%03d', $prefix, $seq + 1);
            }

            // Si no viene precio_bs_base, calculamos con la tasa de registro
            if (is_null($m->precio_bs_base) && $m->precio_usd_base !== null && $m->tasa_usd_registro !== null) {
                $m->precio_bs_base = round((float)$m->precio_usd_base * (float)$m->tasa_usd_registro, 2);
            }
        });

        static::updating(function ($m) {
            // Si cambian USD y NO te mandaron Bs, recalcula con la tasa guardada (no la del día)
            if ($m->isDirty('precio_usd_base') && !$m->isDirty('precio_bs_base')) {
                $tasa = $m->tasa_usd_registro ?: 0;
                $m->precio_bs_base = round((float)$m->precio_usd_base * (float)$tasa, 2);
            }
        });
    }
}
