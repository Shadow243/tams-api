@php
    // Format amount: up to 2 decimal places, trailing zeros stripped
    $fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', ' '), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu — {{ $transaction->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            font-size: 10.5px;
            line-height: 1.45;
            color: #111;
            background: #fff;
            padding: 14px 16px;
        }

        /* ── BRAND HEADER ── */
        .brand {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px dashed #333;
            margin-bottom: 12px;
        }
        .brand-logo {
            height: 40px;
            width: auto;
            margin-bottom: 5px;
        }
        .brand-icon {
            display: inline-block;
            width: 38px; height: 38px;
            background: #1a1a2e;
            color: #fff;
            border-radius: 7px;
            font-size: 20px;
            font-weight: 900;
            line-height: 38px;
            text-align: center;
            margin-bottom: 5px;
        }
        .brand-name { font-size: 17px; font-weight: 900; letter-spacing: 4px; color: #1a1a2e; }
        .brand-sub  { font-size: 7.5px; color: #6b7280; letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }
        .doc-type   { font-size: 9.5px; font-weight: 700; letter-spacing: 2px; margin-top: 7px; text-transform: uppercase; color: #374151; }

        /* ── SECTIONS ── */
        .section {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ccc;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
        }
        .lbl { color: #6b7280; flex-shrink: 0; padding-right: 6px; }
        .val { font-weight: 700; text-align: right; }

        /* ── STATUS BADGE ── */
        @php
            $statusConfig = [
                'pending'   => ['bg' => '#fef9c3', 'color' => '#a16207'],
                'available' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
                'completed' => ['bg' => '#dcfce7', 'color' => '#15803d'],
                'cancelled' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
                'failed'    => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
                'expired'   => ['bg' => '#e5e7eb', 'color' => '#374151'],
            ];
            $sc = $statusConfig[$transaction->status->value] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
        @endphp
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            background: {{ $sc['bg'] }};
            color: {{ $sc['color'] }};
        }

        /* ── WITHDRAWAL CODE ── */
        .code-badge {
            display: inline-block;
            background: #1a1a2e;
            color: #fff;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
            letter-spacing: 2px;
            font-weight: 900;
        }

        /* ── AMOUNTS ── */
        .amounts { margin-bottom: 10px; }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        .amount-lbl { color: #6b7280; font-size: 10.5px; }
        .amount-val { font-weight: 700; font-size: 10.5px; }
        .gross { color: #1d4ed8; }
        .fee   { color: #dc2626; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 6px 0; }

        /* ── NET TOTAL ── */
        .net-total {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 9px 11px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .nt-label { font-size: 9px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }
        .nt-value { font-size: 15px; font-weight: 900; color: #15803d; }

        /* ── EXPIRY ALERT ── */
        .expiry-alert {
            background: #fef9c3;
            border: 1px dashed #a16207;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 9px;
            color: #92400e;
            text-align: center;
            margin-bottom: 10px;
        }

        /* ── BARCODE ── */
        .barcode-area {
            text-align: center;
            margin: 10px 0;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
        }
        .barcode-ref {
            font-size: 9.5px;
            letter-spacing: 2px;
            font-weight: 700;
            color: #374151;
            margin-top: 5px;
        }

        /* ── FOOTER ── */
        .footer {
            text-align: center;
            border-top: 2px dashed #333;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 8px;
            color: #6b7280;
            line-height: 1.7;
        }
    </style>
</head>
<body>

    <!-- Brand header -->
    <div class="brand">
        @if(!empty($logoSrc))
            <img src="{{ $logoSrc }}" alt="TAMS" class="brand-logo">
        @else
            <div class="brand-icon">T</div>
        @endif
        <div class="brand-name">TAMS</div>
        <div class="brand-sub">Transaction &amp; Asset Management System</div>
        <div class="doc-type">Reçu de Transaction</div>
    </div>

    <!-- Transaction info -->
    <div class="section">
        <div class="row">
            <span class="lbl">Référence</span>
            <span class="val" style="letter-spacing:1px">{{ $transaction->reference }}</span>
        </div>
        @if($transaction->withdrawal_code)
        <div class="row">
            <span class="lbl">Code de retrait</span>
            <span class="val"><span class="code-badge">{{ $transaction->withdrawal_code }}</span></span>
        </div>
        @endif
        <div class="row">
            <span class="lbl">Date</span>
            <span class="val">{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span class="lbl">Statut</span>
            <span class="val"><span class="status-badge">{{ $transaction->status->label() }}</span></span>
        </div>
    </div>

    <!-- Details -->
    <div class="section">
        <div class="row">
            <span class="lbl">Type</span>
            <span class="val">{{ $transaction->transactionType->name }}</span>
        </div>
        <div class="row">
            <span class="lbl">Agence</span>
            <span class="val">{{ $transaction->branch->name }}</span>
        </div>
        @if($transaction->destination_branch_id)
        <div class="row">
            <span class="lbl">Destination</span>
            <span class="val">{{ $transaction->destinationBranch->name }}</span>
        </div>
        @endif
        @if($transaction->customer)
        <div class="row">
            <span class="lbl">Client</span>
            <span class="val">{{ $transaction->customer->full_name }}</span>
        </div>
        <div class="row">
            <span class="lbl">Téléphone</span>
            <span class="val">{{ $transaction->customer->phone }}</span>
        </div>
        @elseif($transaction->customer_phone)
        <div class="row">
            <span class="lbl">Téléphone</span>
            <span class="val">{{ $transaction->customer_phone }}</span>
        </div>
        @endif
        @if($transaction->destCustomer)
        <div class="row">
            <span class="lbl">Bénéficiaire</span>
            <span class="val">{{ $transaction->destCustomer->full_name }}</span>
        </div>
        <div class="row">
            <span class="lbl">Tél. bénéficiaire</span>
            <span class="val">{{ $transaction->destCustomer->phone }}</span>
        </div>
        @endif
        <div class="row">
            <span class="lbl">Caissier</span>
            <span class="val">{{ $transaction->user->name }}</span>
        </div>
        <div class="row">
            <span class="lbl">Devise</span>
            <span class="val">
                @if($transaction->currency)
                    {{ $transaction->currency->name }} ({{ $transaction->currency->code }})
                @else
                    {{ $transaction->currency_code ?? 'XAF' }}
                @endif
            </span>
        </div>
    </div>

    <!-- Amounts -->
    <div class="amounts">
        <div class="amount-row">
            <span class="amount-lbl">Montant brut</span>
            <span class="amount-val gross">{{ $fmt($transaction->gross_amount) }} {{ $transaction->currency?->code ?? $transaction->currency_code ?? 'XAF' }}</span>
        </div>
        <div class="amount-row">
            <span class="amount-lbl">Frais</span>
            <span class="amount-val fee">− {{ $fmt($transaction->fee_amount) }} {{ $transaction->currency?->code ?? $transaction->currency_code ?? 'XAF' }}</span>
        </div>
    </div>
    <div class="net-total">
        <span class="nt-label">Montant Net</span>
        <span class="nt-value">{{ $fmt($transaction->net_amount) }} {{ $transaction->currency?->code ?? $transaction->currency_code ?? 'XAF' }}</span>
    </div>

    <!-- Expiry warning -->
    @if($transaction->expires_at && in_array($transaction->status->value, ['pending', 'available']))
    <div class="expiry-alert">
        ⚠ Valable jusqu'au <strong>{{ $transaction->expires_at->format('d/m/Y à H:i') }}</strong>
    </div>
    @endif

    <!-- Barcode -->
    <div class="barcode-area">
        <svg width="190" height="42" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="bc" x="0" y="0" width="8" height="42" patternUnits="userSpaceOnUse">
                    <rect x="0" y="0" width="3" height="42" fill="#1a1a2e"/>
                    <rect x="3" y="0" width="5" height="42" fill="white"/>
                </pattern>
            </defs>
            <rect width="190" height="42" fill="url(#bc)"/>
        </svg>
        <div class="barcode-ref">{{ $transaction->reference }}</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="font-weight:700;font-size:9px;color:#374151">Merci pour votre confiance</div>
        <div>Conservez ce reçu pour toute réclamation</div>
        <div style="margin-top:3px">Imprimé le {{ now()->format('d/m/Y à H:i') }}</div>
        <div style="margin-top:3px;letter-spacing:0.5px">TAMS © {{ now()->year }}</div>
    </div>

</body>
</html>
