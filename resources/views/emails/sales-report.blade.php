<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Ventes</title>
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
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .period {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
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
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .stats-row {
            display: table-row;
        }
        .stat-box {
            display: table-cell;
            width: 50%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .stat-box.green {
            background-color: #d1fae5;
        }
        .stat-box.blue {
            background-color: #dbeafe;
        }
        .stat-box.orange {
            background-color: #fef3c7;
        }
        .stat-box.purple {
            background-color: #ede9fe;
        }
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
        .attachments-info {
            background-color: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .attachments-info h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 16px;
        }
        .attachment-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .attachment-item:last-child {
            border-bottom: none;
        }
        .attachment-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            font-size: 12px;
        }
        .attachment-icon.pdf {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .attachment-icon.excel {
            background-color: #d1fae5;
            color: #059669;
        }
        .attachment-name {
            font-weight: 500;
            color: #374151;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #10b981;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Rapport des Ventes</h1>
            <div class="period">
                {{ $periodLabel }}
                @if($dateFrom && $dateTo)
                    <br>
                    {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                @endif
            </div>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour {{ $recipientName }},
            </div>

            <div class="message">
                Veuillez trouver ci-joint le rapport des ventes que vous avez demandé. Voici un résumé des données :
            </div>

            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stat-box green">
                        <div class="stat-value">{{ $totals['total_sales'] ?? 0 }}</div>
                        <div class="stat-label">Ventes Complétées</div>
                    </div>
                    <div class="stat-box blue">
                        <div class="stat-value">{{ number_format($totals['total_amount'] ?? 0, 0, ',', ' ') }}</div>
                        <div class="stat-label">Montant Total</div>
                    </div>
                </div>
                <div class="stats-row">
                    <div class="stat-box orange">
                        <div class="stat-value">{{ $totals['pending_sales'] ?? 0 }}</div>
                        <div class="stat-label">Ventes en Attente</div>
                    </div>
                    <div class="stat-box purple">
                        <div class="stat-value">{{ number_format($totals['pending_amount'] ?? 0, 0, ',', ' ') }}</div>
                        <div class="stat-label">Montant en Attente</div>
                    </div>
                </div>
            </div>

            <div class="attachments-info">
                <h3>📎 Pièces jointes</h3>
                <div class="attachment-item">
                    <div class="attachment-icon pdf">PDF</div>
                    <div class="attachment-name">rapport_ventes.pdf</div>
                </div>
                <div class="attachment-item">
                    <div class="attachment-icon excel">XLS</div>
                    <div class="attachment-name">rapport_ventes.xlsx</div>
                </div>
            </div>

            <div class="message">
                Les fichiers PDF et Excel contiennent les détails complets de toutes les ventes pour la période sélectionnée.
            </div>
        </div>

        <div class="footer">
            <p>Ce rapport a été généré automatiquement par {{ config('app.name') }}.</p>
            <p>Date de génération : {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>
