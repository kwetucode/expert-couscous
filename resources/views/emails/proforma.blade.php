<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture Proforma {{ $proforma->proforma_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .proforma-number {
            font-size: 18px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            color: #555;
            margin-bottom: 25px;
        }
        .details-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .details-box h3 {
            margin: 0 0 15px 0;
            color: #4f46e5;
            font-size: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
        }
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        .total-row {
            background-color: #4f46e5;
            color: white;
            padding: 12px 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .total-row .detail-label,
        .total-row .detail-value {
            color: white;
        }
        .cta-button {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .cta-button:hover {
            background-color: #4338ca;
        }
        .validity-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #92400e;
        }
        .attachment-notice {
            background-color: #e0f2fe;
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #0369a1;
        }
        .attachment-notice svg {
            vertical-align: middle;
            margin-right: 8px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }
        .company-name {
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Facture Proforma</h1>
            <div class="proforma-number">N° {{ $proforma->proforma_number }}</div>
        </div>

        <div class="content">
            <p class="greeting">Bonjour {{ $proforma->client_name }},</p>

            <p class="message">
                Veuillez trouver ci-joint votre facture proforma émise par <strong>{{ $storeName }}</strong>.
            </p>

            <div class="details-box">
                <h3>📋 Récapitulatif</h3>
                <div class="detail-row">
                    <span class="detail-label">Numéro</span>
                    <span class="detail-value">{{ $proforma->proforma_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date d'émission</span>
                    <span class="detail-value">{{ $proforma->proforma_date->format('d/m/Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nombre d'articles</span>
                    <span class="detail-value">{{ $proforma->items->count() }}</span>
                </div>
                @if($proforma->discount > 0)
                <div class="detail-row">
                    <span class="detail-label">Remise</span>
                    <span class="detail-value" style="color: #dc2626;">-{{ number_format($proforma->discount, 0, ',', ' ') }} {{ current_currency() }}</span>
                </div>
                @endif
                <div class="total-row">
                    <div class="detail-row" style="border: none; padding: 0;">
                        <span class="detail-label">Total</span>
                        <span class="detail-value">{{ number_format($proforma->total, 0, ',', ' ') }} {{ current_currency() }}</span>
                    </div>
                </div>
            </div>

            @if($proforma->valid_until)
            <div class="validity-notice">
                ⏰ <strong>Validité :</strong> Cette proforma est valable jusqu'au <strong>{{ $proforma->valid_until->format('d/m/Y') }}</strong>.
                @if($proforma->isExpired())
                    <br><span style="color: #dc2626;">⚠️ Cette proforma a expiré.</span>
                @endif
            </div>
            @endif

            <div class="attachment-notice">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                </svg>
                <strong>Pièce jointe :</strong> Le document PDF détaillé est joint à cet email.
            </div>

            @if($proforma->notes)
            <div class="details-box">
                <h3>📝 Notes</h3>
                <p style="margin: 0; color: #555;">{{ $proforma->notes }}</p>
            </div>
            @endif

            <p class="message">
                Pour toute question concernant cette proforma, n'hésitez pas à nous contacter.
            </p>
        </div>

        <div class="footer">
            <p class="company-name">{{ $storeName }}</p>
            @if($proforma->store?->address)
                <p>{{ $proforma->store->address }}</p>
            @endif
            @if($proforma->store?->phone)
                <p>Tél: {{ $proforma->store->phone }}</p>
            @endif
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                Ce document est une proposition commerciale et ne constitue pas une facture définitive.
            </p>
        </div>
    </div>
</body>
</html>
