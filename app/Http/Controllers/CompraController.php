<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index()
    {
        return view('compras.index');
    }

    // Tabla JSON
    public function list(Request $request)
    {
        $limit = (int)$request->get('limit', 25);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 25;
        $q = trim((string)$request->get('q', ''));

        $base = Compra::query()
            ->with('proveedor:id,razon_social,rif')
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('numero', 'like', $like)
                        ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', $like)->orWhere('rif', 'like', $like));
                });
            })
            ->orderByDesc('id');

        $paginator = $base->paginate($limit);

        $items = collect($paginator->items())->map(function (Compra $c) {
            return [
                'id'          => $c->id,
                'fecha'       => optional($c->fecha)->format('Y-m-d'),
                'numero'      => $c->numero,
                'proveedor'   => $c->proveedor?->razon_social,
                'rif'         => $c->proveedor?->rif,
                'total_usd'   => (float)$c->total_usd,
                'total_bs'    => (float)$c->total_bs,
                'estado'      => $c->estado,
                'created_at'  => optional($c->created_at)->toDateTimeString(),
            ];
        });

        return response()->json([
            'items' => $items,
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function show(Compra $compra)
    {
        $compra->load(['proveedor:id,razon_social,rif', 'detalles.producto:id,codigo,nombre,unidad,stock']);
        return response()->json([
            'data' => [
                'id'         => $compra->id,
                'fecha'      => optional($compra->fecha)->format('Y-m-d'),
                'numero'     => $compra->numero,
                'proveedor'  => [
                    'id'           => $compra->proveedor?->id,
                    'razon_social' => $compra->proveedor?->razon_social,
                    'rif'          => $compra->proveedor?->rif,
                ],
                'total_usd'  => (float)$compra->total_usd,
                'total_bs'   => (float)$compra->total_bs,
                'estado'     => $compra->estado,
                'detalles'   => $compra->detalles->map(function (CompraDetalle $d) {
                    return [
                        'id'                  => $d->id,
                        'producto_id'         => $d->producto_id,
                        'producto_text'       => $d->producto?->codigo . ' — ' . $d->producto?->nombre,
                        'cantidad'            => (float)$d->cantidad,
                        'precio_unitario_usd' => (float)$d->precio_unitario_usd,
                        'precio_unitario_bs'  => (float)$d->precio_unitario_bs,
                        'subtotal_usd'        => (float)$d->subtotal_usd,
                        'subtotal_bs'         => (float)$d->subtotal_bs,
                    ];
                })->toArray(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validatePayload($request);

        $compra = DB::transaction(function () use ($payload) {
            // Crear compra
            $compra = Compra::create([
                'proveedor_id' => $payload['proveedor_id'],
                'fecha'        => $payload['fecha'] ?? now()->toDateString(),
                'numero'       => $payload['numero'],
                'total_usd'    => 0,
                'total_bs'     => 0,
                'estado'       => 'registrada',
            ]);

            $totUsd = 0;
            $totBs = 0;

            foreach ($payload['items'] as $item) {
                $cantidad = (float)$item['cantidad'];
                $tasa     = (float)$payload['tasa_usd'];

                $pu_usd = isset($item['precio_unitario_usd']) ? (float)$item['precio_unitario_usd'] : null;
                $pu_bs  = isset($item['precio_unitario_bs'])  ? (float)$item['precio_unitario_bs']  : null;

                if ($pu_usd === null && $pu_bs !== null) {
                    $pu_usd = $tasa > 0 ? round($pu_bs / $tasa, 2) : 0.0;
                }
                if ($pu_bs === null && $pu_usd !== null) {
                    $pu_bs = round($pu_usd * $tasa, 2);
                }

                $sub_usd = round($cantidad * (float)$pu_usd, 2);
                $sub_bs  = round($cantidad * (float)$pu_bs, 2);

                // Guarda detalle
                $detalle = CompraDetalle::create([
                    'compra_id'            => $compra->id,
                    'producto_id'          => $item['producto_id'],
                    'cantidad'             => $cantidad,
                    'precio_unitario_usd'  => $pu_usd ?? 0,
                    'precio_unitario_bs'   => $pu_bs  ?? 0,
                    'subtotal_usd'         => $sub_usd,
                    'subtotal_bs'          => $sub_bs,
                ]);

                $totUsd += $sub_usd;
                $totBs  += $sub_bs;

                // Actualiza stock y movimiento (entrada)
                /** @var \App\Models\Producto $producto */
                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);
                $producto->increment('stock', (int) round($cantidad)); // tu tabla de movimientos usa int(11)

                DB::table('inventario_movimientos')->insert([
                    'producto_id'       => $producto->id,
                    'tipo'              => 'entrada',
                    'motivo'            => 'compra',
                    'cantidad'          => (int) round($cantidad),
                    'costo_unitario_usd' => $pu_usd,
                    'costo_unitario_bs' => $pu_bs,
                    'tasa_usd'          => $tasa,
                    'factura_id'        => null,
                    'proveedor_id'      => $payload['proveedor_id'],
                    'detalle'           => 'compra:' . $compra->id,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            $compra->update([
                'total_usd' => $totUsd,
                'total_bs'  => $totBs,
            ]);

            return $compra->fresh(['proveedor']);
        });

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'data'    => ['id' => $compra->id],
        ], 201);
    }

    public function update(Request $request, Compra $compra)
    {
        $payload = $this->validatePayload($request);

        DB::transaction(function () use ($payload, $compra) {
            // Revertir stock y borrar movimientos anteriores de esta compra
            $movs = DB::table('inventario_movimientos')
                ->where('detalle', 'compra:' . $compra->id)
                ->get(['producto_id', 'cantidad']);

            foreach ($movs as $m) {
                Producto::query()
                    ->lockForUpdate()
                    ->where('id', $m->producto_id)
                    ->decrement('stock', (int)$m->cantidad);
            }

            DB::table('inventario_movimientos')
                ->where('detalle', 'compra:' . $compra->id)
                ->delete();

            // Borrar detalles
            $compra->detalles()->delete();

            // Actualizar cabecera
            $compra->update([
                'proveedor_id' => $payload['proveedor_id'],
                'fecha'        => $payload['fecha'] ?? now()->toDateString(),
                'numero'       => $payload['numero'],
                'estado'       => 'registrada',
                'total_usd'    => 0,
                'total_bs'     => 0,
            ]);

            // Re-crear detalles y movimientos
            $totUsd = 0;
            $totBs = 0;
            foreach ($payload['items'] as $item) {
                $cantidad = (float)$item['cantidad'];
                $tasa     = (float)$payload['tasa_usd'];

                $pu_usd = isset($item['precio_unitario_usd']) ? (float)$item['precio_unitario_usd'] : null;
                $pu_bs  = isset($item['precio_unitario_bs'])  ? (float)$item['precio_unitario_bs']  : null;

                if ($pu_usd === null && $pu_bs !== null) {
                    $pu_usd = $tasa > 0 ? round($pu_bs / $tasa, 2) : 0.0;
                }
                if ($pu_bs === null && $pu_usd !== null) {
                    $pu_bs = round($pu_usd * $tasa, 2);
                }

                $sub_usd = round($cantidad * (float)$pu_usd, 2);
                $sub_bs  = round($cantidad * (float)$pu_bs, 2);

                CompraDetalle::create([
                    'compra_id'            => $compra->id,
                    'producto_id'          => $item['producto_id'],
                    'cantidad'             => $cantidad,
                    'precio_unitario_usd'  => $pu_usd ?? 0,
                    'precio_unitario_bs'   => $pu_bs  ?? 0,
                    'subtotal_usd'         => $sub_usd,
                    'subtotal_bs'          => $sub_bs,
                ]);

                $totUsd += $sub_usd;
                $totBs  += $sub_bs;

                $producto = Producto::query()->lockForUpdate()->findOrFail($item['producto_id']);
                $producto->increment('stock', (int) round($cantidad));

                DB::table('inventario_movimientos')->insert([
                    'producto_id'       => $producto->id,
                    'tipo'              => 'entrada',
                    'motivo'            => 'compra',
                    'cantidad'          => (int) round($cantidad),
                    'costo_unitario_usd' => $pu_usd,
                    'costo_unitario_bs' => $pu_bs,
                    'tasa_usd'          => $tasa,
                    'factura_id'        => null,
                    'proveedor_id'      => $payload['proveedor_id'],
                    'detalle'           => 'compra:' . $compra->id,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            $compra->update([
                'total_usd' => $totUsd,
                'total_bs'  => $totBs,
            ]);
        });

        return response()->json(['message' => 'Compra actualizada correctamente.']);
    }

    public function destroy(Compra $compra)
    {
        DB::transaction(function () use ($compra) {
            // Revertir stock y borrar movimientos
            $movs = DB::table('inventario_movimientos')
                ->where('detalle', 'compra:' . $compra->id)
                ->get(['producto_id', 'cantidad']);

            foreach ($movs as $m) {
                Producto::query()
                    ->lockForUpdate()
                    ->where('id', $m->producto_id)
                    ->decrement('stock', (int)$m->cantidad);
            }

            DB::table('inventario_movimientos')
                ->where('detalle', 'compra:' . $compra->id)
                ->delete();

            // Borrar detalles y compra
            $compra->detalles()->delete();
            $compra->delete();
        });

        return response()->json(['message' => 'Compra eliminada correctamente.']);
    }

    // --- Helpers ---
    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'proveedor_id'        => ['required', 'integer', 'exists:proveedores,id'],
            'fecha'               => ['nullable', 'date'],
            'numero'              => ['nullable', 'string', 'max:255'],
            'tasa_usd'            => ['required', 'numeric', 'min:0.0001'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad'    => ['required', 'numeric', 'min:0.01'],
            'items.*.precio_unitario_usd' => ['nullable', 'numeric', 'min:0'],
            'items.*.precio_unitario_bs'  => ['nullable', 'numeric', 'min:0'],
        ]);

        return $data;
    }
}
