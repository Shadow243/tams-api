<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('users.users_list') }}</title>
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

        .gender-cell {
            text-transform: capitalize;
        }

        .verified-icon {
            color: #16a34a;
            font-weight: bold;
            font-size: 10px;
        }

        .unverified-icon {
            color: #dc2626;
            font-weight: bold;
            font-size: 10px;
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
        <div class="report-title">RAPPORT PDF - {{ strtoupper(__('users.users_list')) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 18%;">{{ __('users.table.name') }}</th>
                <th style="width: 15%;">{{ __('users.table.username') }}</th>
                <th style="width: 20%;">{{ __('users.table.email') }}</th>
                <th style="width: 12%;">{{ __('users.table.phone') }}</th>
                <th style="width: 8%;">{{ __('users.table.gender') }}</th>
                <th style="width: 10%;" class="text-center">{{ __('users.table.verified') }}</th>
                <th style="width: 12%;" class="text-center">{{ __('users.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone_number ?? 'N/A' }}</td>
                    <td class="gender-cell">
                        @if($user->gender === 'male' || $user->gender === 'M')
                            {{ __('users.form.male') }}
                        @elseif($user->gender === 'female' || $user->gender === 'F')
                            {{ __('users.form.female') }}
                        @else
                            {{ $user->gender ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="text-center">
                        @if($user->is_email_verified)
                            <span class="verified-icon">✓</span>
                        @else
                            <span class="unverified-icon">✗</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="status-badge {{ $user->active ? 'status-active' : 'status-inactive' }}">
                            {{ $user->active ? __('users.form.active') : __('users.form.inactive') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">{{ __('users.export.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        DATE IMPRESSION {{ strtoupper($date) }}
    </div>
</body>
</html>
