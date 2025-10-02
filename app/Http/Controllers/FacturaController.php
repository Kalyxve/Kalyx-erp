<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $limit = (int)($request->get('limit', 25));
        $inicial = Factura::with(['cliente:id,nombre,apellido', 'vendedor:id,nombre'])
            ->orderByDesc('id')->limit($limit)->get();

        return view('facturas.index', ['facturas' => $inicial]);
    }

    public function list(Request $request)
    {
        $limit  = (int)($request->get('limit', 25));
        $limit  = $limit > 0 && $limit <= 100 ? $limit : 25;
        $q      = trim((string)$request->get('q', ''));
        $estado = trim((string)$request->get('estado', ''));

        $p = Factura::query()
            ->with(['cliente:id,nombre,apellido', 'vendedor:id,nombre'])
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->whereHas('cliente', function ($c) use ($like) {
                    $c->where('nombre', 'like', $like)
                        ->orWhere('apellido', 'like', $like)
                        ->orWhere('rif', 'like', $like);
                });
            })
            ->when($estado !== '', fn($qq) => $qq->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate($limit)
            ->appends(['q' => $q, 'limit' => $limit, 'estado' => $estado]);

        $items = collect($p->items())->map(function ($f) {
            return [
                'id'            => $f->id,
                'cliente'       => trim(($f->cliente->nombre ?? '') . ' ' . ($f->cliente->apellido ?? '')),
                'vendedor'      => $f->vendedor->nombre ?? null,
                'estado'        => $f->estado,
                'tasa_usd'      => (float)$f->tasa_usd,
                'total_usd'     => (float)$f->total_usd,
                'total_bs'      => (float)$f->total_bs,
                'saldo_usd'     => (float)$f->saldo_usd,
                'saldo_bs'      => (float)$f->saldo_bs,
                'fecha_emision' => optional($f->fecha_emision)->format('Y-m-d'),
            ];
        });

        return response()->json([
            'items' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'last_page'    => $p->lastPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
            ]
        ]);
    }

    /**
     * Crear factura (venta o pago_directo).
     *
     * Notas clave:
     * - Sin IVA (quitado de validación y cálculos).
     * - Soporta vendedor_id (select).
     * - tipo_documento: 'venta' (con items) | 'pago_directo' (sin items, solo pagos).
     * - Gate: si queda saldo y no viene permitir_pendiente=true => 422 con faltante.
     * - Calcula y devuelve vuelto_usd/bs (no se guarda).
     * - Para POS/lotería/favor vecina: usar campo pagos[].extra (JSON flexible).
     */
    public function store(Request $request)
    {
        Log::info('FACTURA.IN - raw request', ['payload' => $request->all()]);

        $tipoDocumento = $request->input('tipo_documento', 'venta') === 'pago_directo'
            ? 'pago_directo' : 'venta';

        // ===== Validación dinámica =====
        $rules = [
            'tipo_documento'            => ['nullable', Rule::in(['venta', 'pago_directo'])],
            'cliente_id'                => ['required', 'integer', 'exists:clientes,id'],
            'vendedor_id'               => ['nullable', 'integer', 'exists:vendedores,id'],
            'fecha_emision'             => ['required', 'date'],
            'fecha_vencimiento'         => ['nullable', 'date'],
            'nota'                      => ['nullable', 'string', 'max:500'],
            'tasa_usd'                  => ['required', 'numeric', 'min:0.0001'],

            // pagos
            'pagos'                     => ['nullable', 'array'],
            'pagos.*.metodo'            => ['required_with:pagos', 'string', 'max:30'],
            'pagos.*.monto_usd'         => ['nullable', 'numeric', 'min:0'],
            'pagos.*.monto_bs'          => ['nullable', 'numeric', 'min:0'],
            'pagos.*.tasa_usd'          => ['nullable', 'numeric', 'min:0.0001'],
            'pagos.*.referencia'        => ['nullable', 'string', 'max:120'],
            'pagos.*.extra'             => ['nullable', 'array'],

            'permitir_pendiente'        => ['nullable', 'boolean'],
        ];

        if ($tipoDocumento === 'venta') {
            $rules = array_merge($rules, [
                'items'                     => ['required', 'array', 'min:1'],
                'items.*.producto_id'       => ['required', 'integer', 'exists:productos,id'],
                'items.*.cantidad'          => ['required', 'numeric', 'min:0.001'],
                'items.*.precio_unitario_usd' => ['required', 'numeric', 'min:0'],
                'items.*.tasa_usd_item'     => ['nullable', 'numeric', 'min:0.0001'],
            ]);
        } else {
            // pago_directo: sin items
            $rules = array_merge($rules, [
                'items' => ['nullable', 'array', 'max:0'], // si viene, que esté vacío
            ]);
        }

        $data = Validator::make($request->all(), $rules)->after(function ($v) use ($request) {
            // Reglas extra para POS
            $pagos = $request->input('pagos', []);
            foreach ($pagos as $i => $p) {
                if (($p['metodo'] ?? null) === 'pos') {
                    $extra = $p['extra'] ?? [];
                    if (!isset($extra['voucher']) || trim((string)$extra['voucher']) === '') {
                        $v->errors()->add("pagos.$i.extra.voucher", "El voucher del POS es obligatorio.");
                    }
                    if (!isset($extra['banco']) || trim((string)$extra['banco']) === '') {
                        $v->errors()->add("pagos.$i.extra.banco", "Debe indicar el banco del POS.");
                    }
                }
            }
        })->validate();

        try {
            $tasa   = (float)$data['tasa_usd'];
            $pagadoUsd = 0.0;

            foreach ($data['pagos'] ?? [] as $p) {
                $usd  = (float)($p['monto_usd'] ?? 0);
                $m_bs = (float)($p['monto_bs']  ?? 0);
                $t    = (float)($p['tasa_usd']  ?? $tasa);
                $pagadoUsd += $usd + ($t > 0 ? ($m_bs / $t) : 0);
            }
            $pagadoUsd = round($pagadoUsd, 4);

            // ===== Cálculos por tipo de documento =====
            if ($tipoDocumento === 'pago_directo') {
                // Total = suma de pagos, no hay inventario ni items
                $totalUsd = $pagadoUsd;
                $totalBs  = round($totalUsd * $tasa, 2);
                $saldoUsd = 0.0;
                $saldoBs  = 0.0;
                $vueltoUsd = 0.0;
                $vueltoBs  = 0.0;
                $estado  = 'pagada';

                $factura = DB::transaction(function () use ($data, $tasa, $totalUsd, $totalBs, $saldoUsd, $saldoBs, $estado, $tipoDocumento) {
                    $f = Factura::create([
                        'cliente_id'        => $data['cliente_id'],
                        'vendedor_id'       => $data['vendedor_id'] ?? null,
                        'tipo_documento'    => $tipoDocumento,
                        'estado'            => $estado,
                        'tasa_usd'          => $tasa,
                        'total_usd'         => $totalUsd,
                        'total_bs'          => $totalBs,
                        'saldo_usd'         => $saldoUsd,
                        'saldo_bs'          => $saldoBs,
                        'fecha_emision'     => $data['fecha_emision'],
                        'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                        'nota'              => $data['nota'] ?? null,
                    ]);

                    foreach ($data['pagos'] ?? [] as $p) {
                        Pago::create([
                            'factura_id' => $f->id,
                            'metodo'     => $p['metodo'],
                            'monto_usd'  => $p['monto_usd'] ?? null,
                            'monto_bs'   => $p['monto_bs'] ?? null,
                            'tasa_usd'   => $p['tasa_usd'] ?? $tasa,
                            'referencia' => $p['referencia'] ?? null,
                            'fecha_pago' => now()->toDateString(),
                            'extra'      => $p['extra'] ?? null,
                        ]);
                    }

                    return $f->load(['cliente:id,nombre,apellido', 'vendedor:id,nombre', 'detalles', 'pagos']);
                });

                return response()->json([
                    'message'     => 'Pago directo registrado.',
                    'data'        => $factura,
                    'vuelto_usd'  => $vueltoUsd,
                    'vuelto_bs'   => $vueltoBs,
                    'faltante_usd' => 0.0,
                    'faltante_bs' => 0.0,
                ], 201);
            }

            // ===== Venta (con items) =====
            $subtotalUsd = 0.0;
            foreach ($data['items'] as $it) {
                $cantidad = (float)$it['cantidad'];
                $puUsd    = (float)$it['precio_unitario_usd'];
                $subtotalUsd += $puUsd * $cantidad;
            }
            $subtotalUsd = round($subtotalUsd, 4);

            $totalUsd = $subtotalUsd; // SIN IVA
            $totalBs  = round($totalUsd * $tasa, 2);

            $diff = round($pagadoUsd - $totalUsd, 4);
            $vueltoUsd = $diff > 0 ? $diff : 0.0;
            $vueltoBs  = $vueltoUsd > 0 ? round($vueltoUsd * $tasa, 2) : 0.0;

            $saldoUsd = $diff < 0 ? round(abs($diff), 4) : 0.0;
            $saldoBs  = $saldoUsd > 0 ? round($saldoUsd * $tasa, 2) : 0.0;

            $estado   = $saldoUsd <= 0.0001 ? 'pagada' : 'pendiente';

            // Gate: si queda pendiente y no viene permitir_pendiente=true, abortar 422
            $permitirPendiente = filter_var($data['permitir_pendiente'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($estado === 'pendiente' && !$permitirPendiente) {
                return response()->json([
                    'message'       => 'El pago no cubre el total. Confirma si deseas crear la deuda pendiente.',
                    'faltante_usd'  => $saldoUsd,
                    'faltante_bs'   => $saldoBs,
                ], 422);
            }

            $factura = DB::transaction(function () use ($data, $tasa, $totalUsd, $totalBs, $saldoUsd, $saldoBs, $estado) {
                $f = Factura::create([
                    'cliente_id'        => $data['cliente_id'],
                    'vendedor_id'       => $data['vendedor_id'] ?? null,
                    'tipo_documento'    => 'venta',
                    'estado'            => $estado,
                    'tasa_usd'          => $tasa,
                    'total_usd'         => $totalUsd,
                    'total_bs'          => $totalBs,
                    'saldo_usd'         => $saldoUsd,
                    'saldo_bs'          => $saldoBs,
                    'fecha_emision'     => $data['fecha_emision'],
                    'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                    'nota'              => $data['nota'] ?? null,
                ]);

                foreach ($data['items'] as $it) {
                    $prod  = Producto::findOrFail($it['producto_id']);
                    $cant  = (float)$it['cantidad'];
                    $puUsd = (float)$it['precio_unitario_usd'];
                    $tItem = (float)($it['tasa_usd_item'] ?? $tasa);

                    $puBs   = round($puUsd * $tItem, 2);
                    $subUsd = round($puUsd * $cant, 4);
                    $subBs  = round($puBs  * $cant, 2);

                    FacturaDetalle::create([
                        'factura_id'          => $f->id,
                        'producto_id'         => $prod->id,
                        'cantidad'            => $cant,
                        'precio_unitario_usd' => $puUsd,
                        'precio_unitario_bs'  => $puBs,
                        'subtotal_usd'        => $subUsd,
                        'subtotal_bs'         => $subBs,
                        'tasa_usd_item'       => $tItem,
                    ]);

                    // Movimiento de inventario (salida por venta)
                    Inventario::create([
                        'producto_id'        => $prod->id,
                        'tipo'               => 'salida',
                        'motivo'             => 'venta',
                        'cantidad'           => $cant,
                        'costo_unitario_usd' => $prod->precio_usd_base,
                        'costo_unitario_bs'  => $prod->precio_bs_base,
                        'tasa_usd'           => $prod->tasa_usd_registro,
                        'factura_id'         => $f->id,
                        'proveedor_id'       => null,
                        'detalle'            => 'Salida por venta (factura ' . $f->id . ')',
                    ]);

                    // Descontar stock si existe el campo
                    if ($prod->isFillable('stock') || isset($prod->stock)) {
                        $prod->decrement('stock', $cant);
                    }
                }

                foreach ($data['pagos'] ?? [] as $p) {
                    Pago::create([
                        'factura_id' => $f->id,
                        'metodo'     => $p['metodo'],
                        'monto_usd'  => $p['monto_usd'] ?? null,
                        'monto_bs'   => $p['monto_bs'] ?? null,
                        'tasa_usd'   => $p['tasa_usd'] ?? $tasa,
                        'referencia' => $p['referencia'] ?? null,
                        'fecha_pago' => now()->toDateString(),
                        'extra'      => $p['extra'] ?? null,
                    ]);
                }

                return $f->load(['cliente:id,nombre,apellido', 'vendedor:id,nombre', 'detalles', 'pagos']);
            });

            return response()->json([
                'message'       => 'Factura creada correctamente.',
                'data'          => $factura,
                'vuelto_usd'    => $vueltoUsd,
                'vuelto_bs'     => $vueltoBs,
                'faltante_usd'  => $saldoUsd,
                'faltante_bs'   => $saldoBs,
            ], 201);
        } catch (Throwable $e) {
            Log::error('FACTURA.ERROR', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Error guardando la factura',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Registrar abono a una factura pendiente.
     * Acepta pagos con extra (pos, etc.)
     */
    public function abonar(Request $request, Factura $factura)
    {
        $data = $request->validate([
            'metodo'     => ['required', 'string', 'max:30'],
            'monto_usd'  => ['nullable', 'numeric', 'min:0'],
            'monto_bs'   => ['nullable', 'numeric', 'min:0'],
            'tasa_usd'   => ['nullable', 'numeric', 'min:0.0001'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'fecha_pago' => ['nullable', 'date'],
            'nota'       => ['nullable', 'string', 'max:240'],
            'extra'      => ['nullable', 'array'],
        ]);

        try {
            $tasa = $data['tasa_usd'] ?? $factura->tasa_usd;

            $equivUsd = (float)($data['monto_usd'] ?? 0) + (
                $tasa > 0 ? ((float)($data['monto_bs'] ?? 0) / (float)$tasa) : 0
            );
            $equivUsd = round($equivUsd, 4);
            $equivUsd = min($equivUsd, (float)$factura->saldo_usd); // no sobrepagar

            DB::transaction(function () use ($factura, $data, $equivUsd, $tasa) {
                $usd = (float)($data['monto_usd'] ?? 0);
                $bs  = (float)($data['monto_bs']  ?? 0);

                Pago::create([
                    'factura_id' => $factura->id,
                    'metodo'     => $data['metodo'],
                    'monto_usd'  => $usd ?: null,
                    'monto_bs'   => $bs  ?: null,
                    'tasa_usd'   => $tasa ?: null,
                    'referencia' => $data['referencia'] ?? null,
                    'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                    'nota'       => $data['nota'] ?? null,
                    'extra'      => $data['extra'] ?? null,
                ]);

                $nuevoSaldoUsd = max(0.0, (float)$factura->saldo_usd - $equivUsd);
                $factura->update([
                    'saldo_usd' => $nuevoSaldoUsd,
                    'saldo_bs'  => round($nuevoSaldoUsd * (float)$factura->tasa_usd, 2),
                    'estado'    => $nuevoSaldoUsd <= 0.0001 ? 'pagada' : 'pendiente',
                ]);
            });

            return response()->json(['message' => 'Abono registrado correctamente.']);
        } catch (Throwable $e) {
            Log::error('ABONO.ERROR', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Error registrando abono',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Factura $factura)
    {
        // Cargamos todo lo necesario para la vista
        $factura->load([
            'cliente:id,nombre,apellido,rif',
            'vendedor:id,nombre',
            'detalles.producto:id,nombre,unidad',
            'pagos'
        ]);

        // Por si te piden JSON
        if (request()->wantsJson()) {
            return response()->json($factura);
        }

        return view('facturas.show', compact('factura'));
    }


    /**
     * Anular factura (repone inventario si era venta).
     */
    public function anular(Request $request, Factura $factura)
    {
        if ($factura->estado === 'anulada') {
            return response()->json(['message' => 'La factura ya está anulada.'], 409);
        }

        try {
            DB::transaction(function () use ($factura) {

                // Reponer stock sólo si fue venta con items
                if ($factura->tipo_documento === 'venta') {
                    // Evitamos N+1
                    $factura->load('detalles.producto');

                    foreach ($factura->detalles as $d) {
                        $prod = $d->producto;

                        // Reponer stock si existe el campo
                        if ($prod && ($prod->isFillable('stock') || isset($prod->stock))) {
                            $prod->increment('stock', (float)$d->cantidad);
                        }

                        // Movimiento de inventario (entrada por anulación)
                        Inventario::create([
                            'producto_id'        => $d->producto_id,
                            'tipo'               => 'entrada',
                            // <- Usamos 'anulacion' para evitar el truncamiento
                            'motivo'             => 'anulacion',
                            'cantidad'           => (float)$d->cantidad,
                            'costo_unitario_usd' => $d->precio_unitario_usd,
                            'costo_unitario_bs'  => $d->precio_unitario_bs,
                            'tasa_usd'           => $factura->tasa_usd,
                            'factura_id'         => $factura->id,
                            'proveedor_id'       => null,
                            'detalle'            => 'Entrada por anulación de factura ' . $factura->id,
                        ]);
                    }
                }

                // Cerrar la factura
                $factura->update([
                    'estado'    => 'anulada',
                    'saldo_usd' => 0.0,
                    'saldo_bs'  => 0.0,
                ]);
            });

            return response()->json(['message' => 'Factura anulada correctamente.']);
        } catch (Throwable $e) {
            Log::error('ANULAR.ERROR', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Error anulando factura',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
