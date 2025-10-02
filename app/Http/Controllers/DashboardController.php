<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\FacturaDetalle;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today       = Carbon::today();
        $monthStart  = $today->copy()->startOfMonth();
        $monthEnd    = $today->copy()->endOfMonth();
        $activeSince = $today->copy()->subDays(90);

        $pagosMes = Pago::whereBetween('fecha_pago', [$monthStart, $monthEnd])->get();

        $salesMonthUSD = (float) $pagosMes->sum(function ($p) {
            $usd = (float) $p->monto_usd;
            $bs  = (float) $p->monto_bs;
            $tasa = (float) $p->tasa_usd;
            return round($usd + (($tasa > 0 && $bs > 0) ? $bs / $tasa : 0), 4);
        });

        $countTickets = $pagosMes->count();
        $avgTicketUSD = $countTickets ? round($salesMonthUSD / $countTickets, 2) : 0.0;

        $activeClients = Pago::where('fecha_pago', '>=', $activeSince)
            ->whereHas('factura', fn($q) => $q->whereNotNull('cliente_id'))
            ->with('factura:id,cliente_id')
            ->get()
            ->pluck('factura.cliente_id')->filter()->unique()->count();

        $lowStockCount = Producto::where('activo', true)->where('stock', '<=', 10)->count();

        $sixWeeksAgo = $today->copy()->startOfWeek()->subWeeks(5);
        $pagos6w = Pago::where('fecha_pago', '>=', $sixWeeksAgo)->get();

        $weeklyLabels = [];
        $weeklyData   = [];
        for ($i = 5; $i >= 0; $i--) {
            $ws = $today->copy()->startOfWeek()->subWeeks($i);
            $we = $ws->copy()->endOfWeek();
            $sum = $pagos6w->filter(fn($p) => Carbon::parse($p->fecha_pago)->between($ws, $we))
                ->sum(fn($p) => (float)$p->monto_usd + (($p->tasa_usd > 0 && $p->monto_bs > 0) ? $p->monto_bs / $p->tasa_usd : 0));
            $weeklyLabels[] = $ws->format('M d');
            $weeklyData[]   = round($sum, 2);
        }

        $top = FacturaDetalle::select('producto_id', DB::raw('SUM(cantidad) AS qty'))
            ->whereHas('factura', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('fecha_emision', [$monthStart, $monthEnd])->where('estado', '!=', 'anulada');
            })
            ->groupBy('producto_id')->orderByDesc('qty')->take(5)->get();

        $productos = \App\Models\Producto::whereIn('id', $top->pluck('producto_id'))->get(['id', 'nombre']);
        $mapNames  = $productos->keyBy('id')->map(fn($p) => $p->nombre);

        $topLabels = $top->map(fn($row) => $mapNames[$row->producto_id] ?? ('#' . $row->producto_id))->values()->all();
        $topData   = $top->pluck('qty')->map(fn($x) => (int)$x)->values()->all();

        $settings = ['exchangeRate' => (float) (config('kalyx.exchange_rate') ?? 40.0)];

        return view('pages.dashboard', compact('settings') + [
            'kpis' => [
                'salesMonthUSD' => round($salesMonthUSD, 2),
                'avgTicketUSD'  => $avgTicketUSD,
                'activeClients' => $activeClients,
                'lowStockCount' => $lowStockCount,
            ],
            'chartWeekly' => ['labels' => $weeklyLabels, 'data' => $weeklyData],
            'chartTop'    => ['labels' => $topLabels, 'data' => $topData],
        ]);
    }
}
