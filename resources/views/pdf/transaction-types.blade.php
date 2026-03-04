<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('transaction_types.page_title') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f8f9fa;
            color: #495057;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            font-size: 10px;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e9ecef;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #6c757d;
            padding: 10px;
            border-top: 1px solid #dee2e6;
        }
        .text-center {
            text-align: center;
        }
        .code-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('transaction_types.page_title') }}</h1>
        <p>{{ __('transaction_types.export_subtitle') }} - {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="25%">{{ __('transaction_types.code') }}</th>
                <th width="25%">{{ __('transaction_types.name') }}</th>
                <th width="45%">{{ __('transaction_types.description') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactionTypes as $index => $type)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><span class="code-badge">{{ $type->code }}</span></td>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->description ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">{{ __('transaction_types.no_records') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('transaction_types.generated_on') }} {{ $date }} | {{ config('app.name') }}
    </div>
</body>
</html>
