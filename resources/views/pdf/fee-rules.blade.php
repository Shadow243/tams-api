<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Règles de Frais</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.3;
        }

        .header {
            margin-bottom: 25px;
            border-bottom: 1px solid #ddd;
            position: relative;
        }

        .header-top {
            width: 100%;
            height: 200px;
            position: relative;
        }

        .header-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200px;
            object-fit: cover;
            z-index: 1;
        }

        .color-bar {
            display: table;
            width: 100%;
            height: 4px;
            margin-top: 5px;
        }

        .bar-blue {
            display: table-cell;
            background-color: #2563eb;
            width: 33.33%;
        }

        .bar-yellow {
            display: table-cell;
            background-color: #fbbf24;
            width: 33.33%;
        }

        .bar-red {
            display: table-cell;
            background-color: #dc2626;
            width: 33.34%;
        }

        .report-title-section {
            text-align: center;
            margin: 20px 0 15px 0;
            border: 1px solid #000;
            padding: 8px;
            background-color: #f9fafb;
        }

        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border: 1px solid #333;
        }

        thead {
            background-color: #e5e7eb;
            color: #000;
        }

        th {
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #333;
        }

        td {
            padding: 5px 4px;
            border: 1px solid #333;
            font-size: 8px;
        }

        tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        tbody tr:nth-child(odd) {
            background-color: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }

        .status-active {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .mode-badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }

        .mode-fixed {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .mode-percentage {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .mode-negotiable {
            background-color: #fef3c7;
            color: #d97706;
        }

        .footer {
            position: fixed;
            bottom: 10px;
            right: 20px;
            font-size: 8px;
            color: #000;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <img src="{{ public_path('images/header-background.png') }}" class="header-background" alt="">
        </div>
        <div class="color-bar">
            <div class="bar-blue"></div>
            <div class="bar-yellow"></div>
            <div class="bar-red"></div>
        </div>
    </div>

    <div class="report-title-section">
        <div class="report-title">RAPPORT PDF - RÈGLES DE FRAIS</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 18%;">Type d'opération</th>
                <th style="width: 13%;">Opérateur</th>
                <th style="width: 13%;">Agence</th>
                <th style="width: 10%;">Mode</th>
                <th style="width: 10%;" class="text-right">Valeur</th>
                <th style="width: 10%;" class="text-right">Min</th>
                <th style="width: 10%;" class="text-right">Max</th>
                <th style="width: 11%;" class="text-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($feeRules as $index => $feeRule)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $feeRule->transactionType->name ?? 'N/A' }}</strong><br>
                        <small style="color: #666;">{{ $feeRule->transactionType->code ?? '' }}</small>
                    </td>
                    <td>{{ $feeRule->operator->name ?? '-' }}</td>
                    <td>{{ $feeRule->branch->name ?? '-' }}</td>
                    <td>
                        <span class="mode-badge mode-{{ $feeRule->fee_mode->value }}">
                            {{ $feeRule->fee_mode->label() }}
                        </span>
                    </td>
                    <td class="text-right">
                        @if($feeRule->value !== null)
                            {{ number_format($feeRule->value, 2) }}{{ $feeRule->fee_mode->value === 'percentage' ? '%' : '' }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $feeRule->min_fee !== null ? number_format($feeRule->min_fee, 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $feeRule->max_fee !== null ? number_format($feeRule->max_fee, 2) : '-' }}
                    </td>
                    <td class="text-center">
                        <span class="status-badge {{ $feeRule->is_active ? 'status-active' : 'status-inactive' }}">
                            {{ $feeRule->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Aucune règle de frais trouvée</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        DATE IMPRESSION {{ strtoupper($date) }}
    </div>
</body>
</html>
