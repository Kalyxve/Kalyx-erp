<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index()
    {
        // Vista opcional si más adelante quieres tabla de proveedores
        return view('proveedores.index');
    }

    // Para Tom Select (autocomplete)
    public function list(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $limit = (int)($request->get('limit', 20));
        $limit = $limit > 0 && $limit <= 50 ? $limit : 20;

        $items = Proveedor::query()
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('razon_social', 'like', $like)
                        ->orWhere('rif', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('telefono', 'like', $like);
                });
            })
            ->orderBy('razon_social')
            ->limit($limit)
            ->get(['id', 'razon_social', 'rif', 'activo']);

        $items = $items->map(fn($p) => [
            'id'    => $p->id,
            'text'  => "{$p->rif} — {$p->razon_social}",
            'rif'   => $p->rif,
            'label' => "{$p->razon_social}",
            'activo' => (bool)$p->activo,
        ]);

        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:150'],
            'rif'          => ['required', 'string', 'max:20', 'unique:proveedores,rif'],
            'direccion'    => ['nullable', 'string', 'max:255'],
            'telefono'     => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:120'],
            'activo'       => ['nullable', 'boolean'],
        ]);
        $data['activo'] = array_key_exists('activo', $data) ? (bool)$data['activo'] : true;

        $p = Proveedor::create($data);

        return response()->json([
            'message' => 'Proveedor creado correctamente.',
            'data'    => $p,
            'option'  => [
                'id'   => $p->id,
                'text' => "{$p->rif} — {$p->razon_social}",
            ],
        ], 201);
    }

    public function show(Proveedor $proveedore) // resource route usa parámetro singular del nombre
    {
        return response()->json($proveedore);
    }

    public function update(Request $request, Proveedor $proveedore)
    {
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:150'],
            'rif'          => ['required', 'string', 'max:20', Rule::unique('proveedores', 'rif')->ignore($proveedore->id)],
            'direccion'    => ['nullable', 'string', 'max:255'],
            'telefono'     => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:120'],
            'activo'       => ['nullable', 'boolean'],
        ]);

        $proveedore->update($data);

        return response()->json([
            'message' => 'Proveedor actualizado correctamente.',
            'data'    => $proveedore,
            'option'  => [
                'id'   => $proveedore->id,
                'text' => "{$proveedore->rif} — {$proveedore->razon_social}",
            ],
        ]);
    }

    public function destroy(Proveedor $proveedore)
    {
        if ($proveedore->compras()->exists()) {
            return response()->json([
                'message' => 'No puede eliminar un proveedor con compras asociadas.',
            ], 422);
        }

        $proveedore->delete();

        return response()->json(['message' => 'Proveedor eliminado correctamente.']);
    }
}
