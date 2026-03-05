<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu - {{ $transaction->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 10px;
        }

        .receipt {
            width: 100%;
            max-width: 80mm;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 9px;
            margin: 2px 0;
        }

        .section {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #000;
        }

        .section:last-child {
            border-bottom: none;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .row.highlight {
            font-weight: bold;
            font-size: 11px;
            margin-top: 5px;
        }

        .label {
            text-align: left;
            font-weight: normal;
        }

        .value {
            text-align: right;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            background: #000;
            color: #fff;
            font-weight: bold;
            font-size: 9px;
        }

        .amount-total {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            padding: 5px;
            border: 2px solid #000;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px dashed #000;
            font-size: 8px;
        }

        .barcode {
            text-align: center;
            margin: 10px 0;
        }

        .barcode-lines {
            display: flex;
            justify-content: center;
            margin-bottom: 5px;
        }

        .barcode-line {
            width: 2px;
            height: 40px;
            background: #000;
            margin: 0 1px;
        }

        .barcode-line.thick {
            width: 4px;
        }

        .alert {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 5px;
            margin: 5px 0;
            text-align: center;
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>REÇU DE TRANSACTION</p>
            <p>--------------------------------</p>
        </div>

        <!-- Transaction Info -->
        <div class="section">
            <div class="row">
                <span class="label">Référence:</span>
                <span class="value">{{ $transaction->reference }}</span>
            </div>

            @if($transaction->withdrawal_code)
            <div class="row">
                <span class="label">Code de retrait:</span>
                <span class="value badge">{{ $transaction->withdrawal_code }}</span>
            </div>
            @endif

            <div class="row">
                <span class="label">Date:</span>
                <span class="value">{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="row">
                <span class="label">Statut:</span>
                <span class="value">{{ \App\Enums\TransactionStatus::from($transaction->status)->label() }}</span>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="section">
            <div class="row">
                <span class="label">Type:</span>
                <span class="value">{{ $transaction->transactionType->name }}</span>
            </div>

            <div class="row">
                <span class="label">Agence:</span>
                <span class="value">{{ $transaction->branch->name }}</span>
            </div>

            @if($transaction->destination_branch_id)
            <div class="row">
                <span class="label">Destination:</span>
                <span class="value">{{ $transaction->destinationBranch->name }}</span>
            </div>
            @endif

            @if($transaction->customer)
            <div class="row">
                <span class="label">Client:</span>
                <span class="value">{{ $transaction->customer->full_name }}</span>
            </div>
            <div class="row">
                <span class="label">Téléphone:</span>
                <span class="value">{{ $transaction->customer->phone }}</span>
            </div>
            @elseif($transaction->customer_phone)
            <div class="row">
                <span class="label">Téléphone:</span>
                <span class="value">{{ $transaction->customer_phone }}</span>
            </div>
            @endif

            <div class="row">
                <span class="label">Caissier:</span>
                <span class="value">{{ $transaction->user->name }}</span>
            </div>
        </div>

        <!-- Amounts -->
        <div class="section">
            <div class="row">
                <span class="label">Montant brut:</span>
                <span class="value">{{ number_format($transaction->gross_amount, 0, ',', ' ') }} FCFA</span>
            </div>

            <div class="row">
                <span class="label">Frais:</span>
                <span class="value">- {{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA</span>
            </div>

            <div class="row highlight">
                <span class="label">MONTANT NET:</span>
                <span class="value">{{ number_format($transaction->net_amount, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        <!-- Total Amount (highlighted) -->
        <div class="amount-total">
            {{ number_format($transaction->net_amount, 0, ',', ' ') }} FCFA
        </div>

        <!-- Expiration Warning -->
        @if($transaction->expires_at && in_array($transaction->status, ['pending', 'available']))
        <div class="alert">
            ⚠ VALABLE JUSQU'AU<br>
            <strong>{{ $transaction->expires_at->format('d/m/Y à H:i') }}</strong>
        </div>
        @endif

        <!-- Barcode -->
        <div class="barcode">
            <div class="barcode-lines">
                <div class="barcode-line thick"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line thick"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line thick"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line thick"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line thick"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line thick"></div>
                <div class="barcode-line"></div>
                <div class="barcode-line thick"></div>
            </div>
            <p>{{ $transaction->reference }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="bold">Merci pour votre confiance</p>
            <p>Conservez ce reçu pour toute réclamation</p>
            <p>--------------------------------</p>
            <p>{{ config('app.url') }}</p>
            <p>Imprimé le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>
