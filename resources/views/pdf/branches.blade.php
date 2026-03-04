<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('branches.page_title') }}</title>
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

        .header-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            text-align: center;
            z-index: 2;
        }

        .country-name {
            font-size: 10px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
        }

        .ministry-name {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
        }

        .direction-name {
            font-size: 10px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 2px;
        }

        .system-code {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
            letter-spacing: 2px;
            margin-top: 3px;
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
            <!-- <div class="header-center">
                <div class="country-name">RÉPUBLIQUE DÉMOCRATIQUE DU CONGO</div>
                <div class="ministry-name">SYSTÈME DE GESTION DES TRANSFERTS D'ARGENT</div>
                <div class="direction-name">RAPPOR SYSTEM</div>
                <div class="system-code">T A M S</div>
            </div> -->
        </div>
        <div class="color-bar">
            <div class="bar-blue"></div>
            <div class="bar-yellow"></div>
            <div class="bar-red"></div>
        </div>
    </div>

    <div class="report-title-section">
        <div class="report-title">RAPPORT PDF - {{ strtoupper(__('branches.page_title')) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">{{ __('branches.table.code') }}</th>
                <th style="width: 20%;">{{ __('branches.table.name') }}</th>
                <th style="width: 15%;">{{ __('branches.table.country') }}</th>
                <th style="width: 25%;">{{ __('branches.table.address') }}</th>
                <th style="width: 13%;" class="text-right">{{ __('branches.table.cashBalance') }}</th>
                <th style="width: 10%;" class="text-center">{{ __('branches.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $index => $branch)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $branch->code }}</td>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->country->name ?? 'N/A' }}</td>
                    <td>{{ $branch->address ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($branch->cash_balance, 2) }}</td>
                    <td class="text-center">
                        <span class="status-badge {{ $branch->status->value === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ $branch->status->value === 'active' ? __('branches.status.active') : __('branches.status.inactive') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">{{ __('branches.noResults') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        DATE IMPRESSION {{ strtoupper($date) }}
    </div>
</body>
</html>
