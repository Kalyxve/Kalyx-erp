<?php

namespace App\Http\Controllers;

use App\Models\Tasa;

class RateController extends Controller
{
    /**
     * Devuelve la última tasa vigente (BCV o manual).
     * Respuesta: { usd: 36.5400 }
     */
    public function bcv()
    {
        $t = Tasa::vigente();
        return response()->json([
            'usd' => $t?->valor,
            'fuente' => $t?->fuente,
            'vigente_desde' => optional($t?->vigente_desde)->format('Y-m-d'),
        ]);
    }
}
