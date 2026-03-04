<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('wallets.page_title') }}</title>
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
        <div class="report-title">RAPPORT PDF - {{ strtoupper(__('wallets.page_title')) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 17%;">{{ __('wallets.table.wallet_number') }}</th>
                <th style="width: 20%;">{{ __('wallets.table.branch') }}</th>
                <th style="width: 18%;">{{ __('wallets.table.operator') }}</th>
                <th style="width: 15%;" class="text-right">{{ __('wallets.table.balance') }}</th>
                <th style="width: 10%;" class="text-center">{{ __('wallets.table.currency') }}</th>
                <th style="width: 15%;" class="text-center">{{ __('wallets.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($wallets as $index => $wallet)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $wallet->wallet_number }}</td>
                    <td>{{ $wallet->branch->name ?? 'N/A' }}</td>
                    <td>{{ $wallet->operator->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($wallet->balance, 2) }}</td>
                    <td class="text-center">{{ $wallet->currency }}</td>
                    <td class="text-center">
                        <span class="status-badge {{ $wallet->status->value === 'active' ? 'status-active' : 'status-inactive' }}">
                            {{ $wallet->status->value === 'active' ? __('wallets.status.active') : __('wallets.status.inactive') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">{{ __('wallets.noResults') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        DATE IMPRESSION {{ strtoupper($date) }}
    </div>
</body>
</html>
