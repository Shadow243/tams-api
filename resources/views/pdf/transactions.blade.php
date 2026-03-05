<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Transactions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-secondary { background-color: #e2e3e5; color: #383d41; }
        .badge-dark { background-color: #d6d8db; color: #1b1e21; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        .amount {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Liste des Transactions</h1>
    <div class="header">
        <p>Généré le {{ date('d/m/Y à H:i') }}</p>
        @if(isset($filters['start_date']) && isset($filters['end_date']))
            <p>Période: du {{ $filters['start_date'] }} au {{ $filters['end_date'] }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Référence</th>
                <th>Type</th>
                <th>Agence</th>
                <th>Client</th>
                <th>Montant Brut</th>
                <th>Frais</th>
                <th>Montant Net</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $transaction)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transaction->reference }}</td>
                    <td>{{ $transaction->transactionType->name ?? '-' }}</td>
                    <td>{{ $transaction->branch->name ?? '-' }}</td>
                    <td>
                        {{ $transaction->customer?->full_name ?? $transaction->customer_phone ?? '-' }}
                    </td>
                    <td class="amount">{{ number_format($transaction->gross_amount, 2, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($transaction->fee_amount, 2, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($transaction->net_amount, 2, ',', ' ') }}</td>
                    <td>
                        <span class="badge badge-{{ $transaction->status->color() }}">
                            {{ $transaction->status->label() }}
                        </span>
                    </td>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center;">Aucune transaction trouvée</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré automatiquement - TAMS</p>
    </div>
</body>
</html>
