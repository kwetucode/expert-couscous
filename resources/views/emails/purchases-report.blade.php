<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Achats</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .period-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 15px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .stat-box.green { border-left: 4px solid #10b981; }
        .stat-box.blue { border-left: 4px solid #3b82f6; }
        .stat-box.amber { border-left: 4px solid #f59e0b; }
        .stat-box.purple { border-left: 4px solid #8b5cf6; }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .attachments {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
            border: 1px solid #e5e7eb;
        }
        .attachments h3 {
            margin: 0 0 15px;
            color: #374151;
            font-size: 14px;
        }
        .attachment-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .attachment-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .attachment-icon.pdf { background: #ef4444; }
        .attachment-icon.excel { background: #10b981; }
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 20px 30px;
            border-radius: 0 0 10px 10px;
            text-align: center;
            font-size: 12px;
        }
        .footer a {
            color: #60a5fa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Rapport des Achats</h1>
        <p>{{ config('app.name', 'EasyVente') }}</p>
    </div>

    <div class="content">
        <p class="greeting">Bonjour <strong>{{ $recipientName }}</strong>,</p>

        <p>Veuillez trouver ci-joint le rapport des achats pour la période sélectionnée.</p>

        <div class="period-badge">
            📅 {{ $periodLabel }}
            @if($dateFrom && $dateTo)
                ({{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }})
            @endif
        </div>

        @if(!empty($totals))
        <div class="stats-grid">
            <div class="stat-box green">
                <div class="stat-value">{{ $totals['total_purchases'] ?? 0 }}</div>
                <div class="stat-label">Achats Réceptionnés</div>
            </div>
            <div class="stat-box blue">
                <div class="stat-value">{{ number_format($totals['total_amount'] ?? 0, 0, ',', ' ') }} FCFA</div>
                <div class="stat-label">Montant Total</div>
            </div>
            <div class="stat-box amber">
                <div class="stat-value">{{ $totals['pending_purchases'] ?? 0 }}</div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-box purple">
                <div class="stat-value">{{ number_format($totals['pending_amount'] ?? 0, 0, ',', ' ') }} FCFA</div>
                <div class="stat-label">Montant en Attente</div>
            </div>
        </div>
        @endif

        <div class="attachments">
            <h3>📎 Pièces jointes</h3>
            <div class="attachment-item">
                <div class="attachment-icon pdf">PDF</div>
                <div>
                    <strong>rapport_achats.pdf</strong>
                    <div style="font-size: 12px; color: #6b7280;">Rapport détaillé au format PDF</div>
                </div>
            </div>
            <div class="attachment-item">
                <div class="attachment-icon excel">XLS</div>
                <div>
                    <strong>rapport_achats.xlsx</strong>
                    <div style="font-size: 12px; color: #6b7280;">Données exportables au format Excel</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Ce message a été envoyé automatiquement par {{ config('app.name', 'EasyVente') }}</p>
        <p>© {{ date('Y') }} {{ config('app.name', 'EasyVente') }}. Tous droits réservés.</p>
    </div>
</body>
</html>
