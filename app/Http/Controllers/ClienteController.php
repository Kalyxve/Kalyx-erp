<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Vista principal (tabla + modales).
     */
    public function index(Request $request)
    {
        $limit = (int)($request->get('limit', 25));
        $q     = trim((string)$request->get('q', ''));

        // Cargamos un primer set para el render inicial (mejor UX)
        $baseQuery = Cliente::query()
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('nombre', 'like', $like)
                        ->orWhere('apellido', 'like', $like)
                        ->orWhere('rif', 'like', $like)
                        ->orWhere('telefono', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderByDesc('id');

        $clientes = $baseQuery->limit($limit)->get();

        return view('clientes.index', compact('clientes'));
    }

    /**
     * Listado JSON con paginación para la tabla (AJAX).
     */
    public function list(Request $request)
    {
        $limit = (int)($request->get('limit', 25));
        $limit = $limit > 0 && $limit <= 100 ? $limit : 25;
        $q     = trim((string)$request->get('q', ''));

        $paginator = Cliente::query()
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('nombre', 'like', $like)
                        ->orWhere('apellido', 'like', $like)
                        ->orWhere('rif', 'like', $like)
                        ->orWhere('telefono', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate($limit)
            ->appends(['q' => $q, 'limit' => $limit]);

        return response()->json([
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Crear (AJAX).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'apellido'  => ['nullable', 'string', 'max:100'],
            'rif'       => ['required', 'string', 'max:20', 'unique:clientes,rif'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'string', 'max:120', 'email'],
            'activo'    => ['required', 'boolean'],
        ]);

        $cliente = Cliente::create($data);

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data'    => $cliente,
        ], 201);
    }

    /**
     * Mostrar uno (opcional, se usa para prellenar si quisieras).
     */
    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    /**
     * Actualizar (AJAX).
     */
    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'apellido'  => ['nullable', 'string', 'max:100'],
            'rif'       => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'rif')->ignore($cliente->id),
            ],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'string', 'max:120', 'email'],
            'activo'    => ['required', 'boolean'],
        ]);

        $cliente->update($data);

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data'    => $cliente,
        ]);
    }

    /**
     * Eliminar (AJAX).
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }

    // No usamos create/edit con vistas porque todo es modal.
}
