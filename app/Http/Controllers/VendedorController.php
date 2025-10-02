<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use Illuminate\Http\Request;

class VendedorController extends Controller
{
    public function list(Request $request)
    {
        $limit = (int)($request->get('limit', 20));
        $q     = trim((string)$request->get('q', ''));

        $query = Vendedor::query()->orderBy('nombre');

        if ($q !== '') {
            $query->where('nombre', 'like', "%{$q}%")
                ->orWhere('telefono', 'like', "%{$q}%");
        }

        $items = $query->limit($limit)->get(['id', 'nombre', 'telefono']);

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'   => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'activo'   => ['boolean'],
        ]);

        $v = Vendedor::create($data);

        return response()->json([
            'message' => 'Vendedor creado correctamente',
            'data'    => $v,
        ]);
    }
}
