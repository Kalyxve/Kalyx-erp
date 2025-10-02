<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $limit = (int)($request->get('limit', 25));
        $q = trim((string)$request->get('q', ''));
        $categoriaId = (int)($request->get('categoria_id', 0));

        $base = Producto::query()
            ->with('categoria:id,nombre,slug')
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('nombre', 'like', $like)
                        ->orWhere('codigo', 'like', $like)
                        ->orWhere('unidad', 'like', $like);
                });
            })
            ->when($categoriaId > 0, fn($qq) => $qq->where('categoria_id', $categoriaId))
            ->orderByDesc('id');

        $productos = $base->limit($limit)->get();

        $categorias = Categoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug']);

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function list(Request $request)
    {
        $limit = (int)($request->get('limit', 25));
        $limit = $limit > 0 && $limit <= 100 ? $limit : 25;
        $q = trim((string)$request->get('q', ''));
        $categoriaId = (int)($request->get('categoria_id', 0));

        $paginator = Producto::query()
            ->with('categoria:id,nombre,slug')
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('nombre', 'like', $like)
                        ->orWhere('codigo', 'like', $like)
                        ->orWhere('unidad', 'like', $like);
                });
            })
            ->when($categoriaId > 0, fn($qq) => $qq->where('categoria_id', $categoriaId))
            ->orderByDesc('id')
            ->paginate($limit)
            ->appends(['q' => $q, 'limit' => $limit, 'categoria_id' => $categoriaId]);

        $items = collect($paginator->items())->map(function ($p) {
            return [
                'id'                => $p->id,
                'codigo'            => $p->codigo,
                'nombre'            => $p->nombre,
                'categoria_id'      => $p->categoria_id,
                'categoria_nombre'  => $p->categoria->nombre ?? null,
                'unidad'            => $p->unidad,
                'stock'             => (float)$p->stock,
                'precio_usd_base'   => (float)$p->precio_usd_base,
                'precio_bs_base'    => (float)$p->precio_bs_base,
                'tasa_usd_registro' => (float)$p->tasa_usd_registro,
                'activo'            => (bool)$p->activo,
            ];
        });

        return response()->json([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    // NUEVO: para Tom Select (productos)
    public function combo(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $limit = (int) $request->get('limit', 20);

        $base = Producto::query()
            ->where('activo', 1)
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('nombre', 'like', $like)
                        ->orWhere('codigo', 'like', $like);
                });
            })
            ->orderBy('nombre')
            ->limit($limit)
            ->get();

        $items = $base->map(function ($p) {
            return [
                'id'   => $p->id,
                'text' => "{$p->codigo} — {$p->nombre} ({$p->unidad})",
                'precio_usd_base' => (float)$p->precio_usd_base,
                'precio_bs_base'  => (float)$p->precio_bs_base,
            ];
        });

        return response()->json(['items' => $items]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'categoria_id'       => ['required', 'integer', 'exists:categorias,id'],
            'nombre'             => ['required', 'string', 'max:150'],
            'unidad'             => ['required', 'string', 'max:20'],
            'stock'              => ['nullable', 'numeric', 'min:0'],
            'precio_usd_base'    => ['required', 'numeric', 'min:0'],
            'tasa_usd_registro'  => ['required', 'numeric', 'min:0.0001'],
            'precio_bs_base'     => ['nullable', 'numeric', 'min:0'],
            'activo'             => ['required', 'boolean'],
        ]);

        $producto = \App\Models\Producto::create($data);

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data'    => $producto->load('categoria:id,nombre,slug'),
        ], 201);
    }

    public function update(Request $request, \App\Models\Producto $producto)
    {
        $data = $request->validate([
            'categoria_id'       => ['required', 'integer', 'exists:categorias,id'],
            'nombre'             => ['required', 'string', 'max:150'],
            'unidad'             => ['required', 'string', 'max:20'],
            'stock'              => ['nullable', 'numeric', 'min:0'],
            'precio_usd_base'    => ['required', 'numeric', 'min:0'],
            'tasa_usd_registro'  => ['required', 'numeric', 'min:0.0001'],
            'precio_bs_base'     => ['nullable', 'numeric', 'min:0'],
            'activo'             => ['required', 'boolean'],
        ]);

        $producto->update($data);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data'    => $producto->load('categoria:id,nombre,slug'),
        ]);
    }

    public function destroy(\App\Models\Producto $producto)
    {
        $producto->delete();
        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }
}
