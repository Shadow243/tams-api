@php
    // Format amount: up to 2 decimal places, trailing zeros stripped
    $fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', ' '), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des Transactions — TAMS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            padding: 24px 28px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 3px solid #0d6efd;
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-logo {
            height: 44px;
            width: auto;
            display: block;
        }
        .brand-logo-fallback {
            width: 44px; height: 44px;
            background: #0d6efd;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-weight: 900;
            font-size: 20px;
            letter-spacing: -1px;
        }
        .brand-text .name {
            font-size: 20px;
            font-weight: 700;
            color: #0d6efd;
            line-height: 1;
        }
        .brand-text .tagline {
            font-size: 9px;
            color: #6c757d;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .report-meta { text-align: right; }
        .report-meta .report-title {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .report-meta p {
            font-size: 9px;
            color: #6c757d;
            margin-top: 3px;
            line-height: 1.5;
        }

        /* ── Filters bar ── */
        .filters-bar {
            background: #f0f4ff;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 18px;
            font-size: 10px;
            color: #374151;
        }
        .filters-bar strong { color: #0d6efd; }

        /* ── Currency group header ── */
        .currency-group {
            margin-bottom: 22px;
        }
        .currency-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .currency-pill {
            background: #1a1a2e;
            color: #fff;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .currency-count {
            font-size: 10px;
            color: #6c757d;
        }

        /* ── Summary cards ── */
        .summary-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .summary-card {
            flex: 1;
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #e9ecef;
        }
        .summary-card .sc-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .summary-card .sc-value {
            font-size: 14px;
            font-weight: 700;
        }
        .card-blue   { background: #eff6ff; border-color: #bfdbfe; }
        .card-blue   .sc-value { color: #1d4ed8; }
        .card-red    { background: #fff5f5; border-color: #fecaca; }
        .card-red    .sc-value { color: #dc2626; }
        .card-green  { background: #f0fdf4; border-color: #bbf7d0; }
        .card-green  .sc-value { color: #16a34a; }
        .card-neutral { background: #f9fafb; border-color: #e5e7eb; }
        .card-neutral .sc-value { color: #374151; }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        thead tr {
            background: #1a1a2e;
            color: #fff;
        }
        thead th {
            padding: 9px 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }
        thead th:first-child { border-radius: 4px 0 0 0; }
        thead th:last-child  { border-radius: 0 4px 0 0; }
        tbody tr { border-bottom: 1px solid #f1f3f5; }
        tbody tr:hover { background: #f8faff; }
        tbody tr:nth-child(even) { background: #fafbff; }
        tbody td {
            padding: 7px 8px;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-mono { font-family: 'Courier New', monospace; }
        .fw-bold { font-weight: 700; }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-success   { background: #dcfce7; color: #15803d; }
        .badge-warning   { background: #fef9c3; color: #a16207; }
        .badge-info      { background: #dbeafe; color: #1d4ed8; }
        .badge-danger    { background: #fee2e2; color: #b91c1c; }
        .badge-secondary { background: #f3f4f6; color: #4b5563; }
        .badge-dark      { background: #e5e7eb; color: #111827; }

        /* ── Totals footer row ── */
        .totals-row td {
            background: #1a1a2e;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            padding: 9px 8px;
            border: none;
        }
        .totals-row td:first-child { border-radius: 0 0 0 4px; }
        .totals-row td:last-child  { border-radius: 0 0 4px 0; }

        .amount-gross { color: #93c5fd; }
        .amount-fee   { color: #fca5a5; }
        .amount-net   { color: #86efac; }

        /* ── Grand total table (multi-currency) ── */
        .grand-total {
            margin-top: 6px;
            margin-bottom: 18px;
        }
        .grand-total-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 6px;
        }
        .grand-total table { margin-bottom: 0; }
        .grand-total thead tr { background: #374151; }
        .grand-total tbody td { background: #f9fafb; }
        .grand-total .totals-row td { background: #0d6efd; }

        /* ── Page footer ── */
        .page-footer {
            border-top: 1px solid #e9ecef;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #9ca3af;
        }

        /* ── Empty state ── */
        .empty { text-align: center; padding: 32px; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>

    {{-- ── Header ── --}}
    <div class="header">
        <div class="brand">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="TAMS" class="brand-logo">
            @else
                <div class="brand-logo-fallback">T</div>
            @endif
            <div class="brand-text">
                <div class="name">TAMS</div>
                <div class="tagline">Transaction &amp; Asset Management System</div>
            </div>
        </div>
        <div class="report-meta">
            <div class="report-title">Rapport des Transactions</div>
            <p>Généré le {{ date('d/m/Y à H:i') }}</p>
            <p>Total : {{ $totalCount }} transaction(s) · {{ $groups->count() }} devise(s)</p>
        </div>
    </div>

    {{-- ── Active filters ── --}}
    @if(array_filter($filters))
    <div class="filters-bar">
        <strong>Filtres appliqués :</strong>
        @if(!empty($filters['start_date'])) &nbsp;Du <strong>{{ $filters['start_date'] }}</strong>@endif
        @if(!empty($filters['end_date'])) &nbsp;au <strong>{{ $filters['end_date'] }}</strong>@endif
        @if(!empty($filters['status'])) &nbsp;· Statut : <strong>{{ ucfirst($filters['status']) }}</strong>@endif
        @if(!empty($filters['search'])) &nbsp;· Recherche : <strong>{{ $filters['search'] }}</strong>@endif
    </div>
    @endif

    {{-- ── Grand totals recap when multiple currencies ── --}}
    @if($groups->count() > 1)
    <div class="grand-total">
        <div class="grand-total-title">Récapitulatif par devise</div>
        <table>
            <thead>
                <tr>
                    <th>Devise</th>
                    <th class="text-center">Transactions</th>
                    <th class="text-right">Montant Brut</th>
                    <th class="text-right">Frais</th>
                    <th class="text-right">Montant Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $g)
                <tr>
                    <td class="fw-bold">{{ $g['currency_code'] }}</td>
                    <td class="text-center">{{ $g['count'] }}</td>
                    <td class="text-right fw-bold" style="color:#1d4ed8">{{ $fmt($g['gross']) }} {{ $g['currency_code'] }}</td>
                    <td class="text-right" style="color:#dc2626">{{ $fmt($g['fee']) }} {{ $g['currency_code'] }}</td>
                    <td class="text-right fw-bold" style="color:#16a34a">{{ $fmt($g['net']) }} {{ $g['currency_code'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── One section per currency ── --}}
    @forelse($groups as $group)
    <div class="currency-group">

        {{-- Currency heading --}}
        <div class="currency-heading">
            <span class="currency-pill">{{ $group['currency_code'] }}</span>
            @if(!empty($group['currency_name']))
            <span class="currency-count">{{ $group['currency_name'] }} &mdash;</span>
            @endif
            <span class="currency-count">{{ $group['count'] }} transaction(s)</span>
        </div>

        {{-- Summary cards --}}
        <div class="summary-row">
            <div class="summary-card card-neutral">
                <div class="sc-label">Transactions</div>
                <div class="sc-value">{{ $group['count'] }}</div>
            </div>
            <div class="summary-card card-blue">
                <div class="sc-label">Montant Total</div>
                <div class="sc-value">{{ $fmt($group['gross']) }} {{ $group['currency_code'] }}</div>
            </div>
            <div class="summary-card card-red">
                <div class="sc-label">Total Frais</div>
                <div class="sc-value">{{ $fmt($group['fee']) }} {{ $group['currency_code'] }}</div>
            </div>
            <div class="summary-card card-green">
                <div class="sc-label">Montant Net Total</div>
                <div class="sc-value">{{ $fmt($group['net']) }} {{ $group['currency_code'] }}</div>
            </div>
        </div>

        {{-- Transactions table --}}
        <table>
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>Référence</th>
                    <th>Type</th>
                    <th>Agence</th>
                    <th>Client</th>
                    <th class="text-right">Montant Brut</th>
                    <th class="text-right">Frais</th>
                    <th class="text-right">Montant Net</th>
                    <th class="text-center">Statut</th>
                    <th class="text-center">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['transactions'] as $index => $transaction)
                <tr>
                    <td class="text-center text-mono" style="color:#9ca3af">{{ $index + 1 }}</td>
                    <td class="text-mono fw-bold" style="font-size:10px">{{ $transaction->reference }}</td>
                    <td>{{ $transaction->transactionType->name ?? '-' }}</td>
                    <td>{{ $transaction->branch->name ?? '-' }}</td>
                    <td>{{ $transaction->customer?->full_name ?? ($transaction->customer_phone ?? '-') }}</td>
                    <td class="text-right fw-bold" style="color:#1d4ed8">
                        {{ $fmt($transaction->gross_amount) }}
                    </td>
                    <td class="text-right" style="color:#dc2626">
                        {{ $fmt($transaction->fee_amount) }}
                    </td>
                    <td class="text-right fw-bold" style="color:#16a34a">
                        {{ $fmt($transaction->net_amount) }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $transaction->status->color() }}">
                            {{ $transaction->status->label() }}
                        </span>
                    </td>
                    <td class="text-center" style="color:#6b7280; font-size:9.5px">
                        {{ $transaction->created_at->format('d/m/Y') }}<br>
                        <span style="color:#9ca3af">{{ $transaction->created_at->format('H:i') }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5" class="fw-bold">TOTAUX {{ $group['currency_code'] }} ({{ $group['count'] }} transactions)</td>
                    <td class="text-right amount-gross">{{ $fmt($group['gross']) }} {{ $group['currency_code'] }}</td>
                    <td class="text-right amount-fee">{{ $fmt($group['fee']) }} {{ $group['currency_code'] }}</td>
                    <td class="text-right amount-net">{{ $fmt($group['net']) }} {{ $group['currency_code'] }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    </div>
    @empty
    <p style="text-align:center;color:#9ca3af;padding:32px">Aucune transaction trouvée</p>
    @endforelse

    {{-- ── Page footer ── --}}
    <div class="page-footer">
        <span>TAMS — Transaction &amp; Asset Management System</span>
        <span>Document confidentiel — Usage interne uniquement</span>
        <span>Généré le {{ date('d/m/Y à H:i') }}</span>
    </div>

</body>
</html>
