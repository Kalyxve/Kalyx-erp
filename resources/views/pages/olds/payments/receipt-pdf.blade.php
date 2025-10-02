{{-- resources/views/pages/payments/receipt-pdf.blade.php --}}
@php
    $GREEN = '#0E8F60';
    $GRAY = '#6B7280';
    $BORD = '#E5E7EB';

    $fmt = fn($n) => number_format((float) $n, 2, ',', '.');

    $rate = (float) ($payment['rateUsed'] ?? 0);
    $amountUSD = (float) ($payment['amountUSD'] ?? 0);
    $amountVES = (float) ($payment['amountVES'] ?? 0);
    $subtotal = (float) ($payment['subtotalUSD'] ?? 0);
    $discount = (float) ($payment['discountUSD'] ?? 0);
    $tax = (float) ($payment['taxUSD'] ?? 0);

    $ref = $payment['reference'] ?? ($payment['id'] ?? '—');
    $date = \Carbon\Carbon::parse($payment['date'] ?? now())->format('d/m/Y');
    $met = strtoupper($payment['method'] ?? '—');
    $curr = strtoupper($payment['currency'] ?? 'USD');

    $recib = $payment['received'] ?? null;
    $vuelto = $payment['change'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo de pago</title>
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 8mm 10mm;
        }

        /* margen más compacto */

        body {
            font-size: 11px;
        }

        /* baja 1pt para compactar */

        /* CABECERA */
        .header {
            padding: 14px 18px;
            color: #fff;
            background: {{ $GREEN }};
        }

        .h-row {
            width: 100%;
            border-collapse: collapse;
        }

        .h-row td {
            vertical-align: top;
        }

        .brand {
            display: inline-block;
        }

        .brand img {
            width: 46px;
            height: 46px;
            vertical-align: middle;
        }

        .brand .app {
            display: inline-block;
            margin-left: 10px;
        }

        .brand .name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .2px;
            line-height: 1.2;
        }

        .brand .subtitle {
            font-size: 12px;
            opacity: .9;
            margin-top: 2px;
        }

        .qr-box {
            text-align: right;
        }

        .qr {
            background: #fff;
            padding: 6px;
            border-radius: 8px;
            width: 74px;
            height: 74px;
            display: inline-block;
        }

        .card {
            margin: 12px 14px;
            /* menos margen */
            padding: 10px;
            /* menos padding */
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            /* ❌ QUITAR page-break-inside: avoid AQUÍ */
        }

        .title-sm {
            font-weight: 700;
            color: #374151;
            margin: 0 0 6px 0;
        }

        .muted {
            color: {{ $GRAY }};
        }

        .kv {
            margin: 2px 0;
        }

        .grid2 {
            width: 100%;
            border-collapse: collapse;
        }

        .grid2 td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        /* TABLA ITEMS */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items th {
            text-align: left;
            background: #F3F4F6;
            color: #374151;
            border-bottom: 1px solid {{ $BORD }};
            padding: 7px;
        }

        .items td {
            border-bottom: 1px solid {{ $BORD }};
            padding: 7px;
        }

        .right {
            text-align: right;
        }

        /* RESUMEN/TOTALES */
        .totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .totals td {
            padding: 4px 0;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            background: #F0FDF4;
            border: 1px solid #86EFAC;
            color: #065F46;
            font-weight: 700;
        }

        .big {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .2px;
        }

        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #ECFDF5;
            color: #065F46;
            font-weight: 700;
            font-size: 11px;
            border: 1px solid #A7F3D0;
        }

        /* PIE */
        .footer {
            text-align: center;
            font-size: 11px;
            color: {{ $GRAY }};
            padding: 10px 16px;
        }

        /* “Firmas” (opcional para pyme) */
        .sign {
            margin-top: 10px;
            display: flex;
            gap: 16px;
        }

        .sign>div {
            flex: 1;
        }

        .line {
            border-top: 1px dashed {{ $BORD }};
            padding-top: 4px;
            text-align: center;
            color: {{ $GRAY }};
        }

        /* Evitar cortes feos */
        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <table class="h-row">
            <tr>
                <td>
                    <div class="brand">
                        <img src="{{ public_path('logo.png') }}" alt="logo">
                        <div class="app">
                            <div class="name">{{ $appName }}</div>
                            <div class="subtitle">Recibo de pago</div>
                        </div>
                    </div>
                </td>
                <td class="qr-box">
                    @if (!empty($qrData))
                        <img src="{{ $qrData }}" class="qr" alt="qr">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- BLOQUE CLIENTE / PAGO --}}
    <div class="card no-break">
        <table class="grid2">
            <tr>
                <td>
                    <p class="title-sm">Cliente</p>
                    <div class="kv"><b>Nombre:</b> {{ $client['name'] ?? '—' }}</div>
                    <div class="kv"><b>Email:</b> {{ $client['email'] ?? '—' }}</div>
                    <div class="kv"><b>Teléfono:</b> {{ $client['phone'] ?? '—' }}</div>
                    @if (!empty($client['address']))
                        <div class="kv"><b>Dirección:</b> {{ $client['address'] }}</div>
                    @endif
                </td>
                <td>
                    <p class="title-sm">Pago</p>
                    <div class="kv"><b>Fecha:</b> {{ $date }}</div>
                    <div class="kv"><b>Método:</b> {{ $met }}</div>
                    <div class="kv"><b>Referencia:</b> {{ $ref }}</div>
                    <div class="kv"><b>Moneda de registro:</b> {{ $curr }}</div>
                    <div class="kv">
                        <b>Tasa BCV:</b> {{ $rate ? $fmt($rate) : '—' }} Bs/USD
                        @if (!empty($payment['rateDate']))
                            <span class="muted">( {{ $payment['rateDate'] }} )</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ITEMS --}}
    <div class="card">
        <table class="items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th class="right">Precio USD</th>
                    <th class="right">Cant.</th>
                    <th class="right">Total USD</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($payment['items'] ?? []) as $it)
                    <tr>
                        <td>{{ $it['name'] ?? '—' }}</td>
                        <td>{{ $it['sku'] ?? '—' }}</td>
                        <td class="right">$ {{ $fmt($it['priceUSD'] ?? 0) }}</td>
                        <td class="right">{{ $it['qty'] ?? 0 }}</td>
                        <td class="right">$ {{ $fmt($it['totalUSD'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">Sin ítems registrados…</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td style="width:60%"></td>
                <td>
                    <div class="kv"><b>Subtotal:</b> $ {{ $fmt($subtotal) }}</div>
                    <div class="kv"><b>Descuento:</b> $ {{ $fmt($discount) }}</div>
                    <div class="kv"><b>Impuesto:</b> $ {{ $fmt($tax) }}</div>
                    <div class="kv" style="margin-top:6px;">
                        <span class="badge">
                            <span class="big">Total: $ {{ $fmt($amountUSD) }}</span>
                            &nbsp; | &nbsp;
                            Bs {{ $fmt($amountVES) }}
                        </span>
                    </div>

                    @if ($rate)
                        <div class="kv" style="margin-top:6px;">
                            <span class="pill">1 USD = {{ $fmt($rate) }} Bs</span>
                        </div>
                    @endif

                    @if ($payment['method'] === 'cash' && $recib !== null)
                        <div class="kv" style="margin-top:8px;">
                            <b>Detalle efectivo:</b>
                            <span class="muted">
                                Recibido {{ $curr === 'USD' ? '$' : 'Bs' }} {{ $fmt($recib) }}
                                @if ($vuelto !== null)
                                    — Vuelto {{ $curr === 'USD' ? '$' : 'Bs' }} {{ $fmt($vuelto) }}
                                @endif
                            </span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Notas --}}
        @if (!empty($payment['notes']))
            <div class="kv" style="margin-top:10px;">
                <b>Notas:</b> {{ $payment['notes'] }}
            </div>
        @endif

        {{-- Firmas (opcionales para pyme) --}}
        <div class="sign">
            <div>
                <div class="line">Recibido por</div>
            </div>
            <div>
                <div class="line">Cliente</div>
            </div>
        </div>
    </div>

    <div class="footer">Gracias por su preferencia — {{ $appName }}</div>
</body>

</html>
