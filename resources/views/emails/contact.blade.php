<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message de contact</title>
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
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 30px;
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
            padding: 30px;
        }
        .field {
            margin-bottom: 20px;
        }
        .field-label {
            font-weight: 600;
            color: #6366f1;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .field-value {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 3px solid #6366f1;
        }
        .message-content {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 3px solid #8b5cf6;
            white-space: pre-wrap;
        }
        .footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📬 Nouveau message de contact</h1>
            <p>Via {{ config('app.name', 'EasyVente') }}</p>
        </div>

        <div class="content">
            <div class="field">
                <div class="field-label">Nom</div>
                <div class="field-value">{{ $name }}</div>
            </div>

            <div class="field">
                <div class="field-label">Email</div>
                <div class="field-value">
                    <a href="mailto:{{ $email }}" style="color: #6366f1;">{{ $email }}</a>
                </div>
            </div>

            <div class="field">
                <div class="field-label">Sujet</div>
                <div class="field-value">{{ $subject }}</div>
            </div>

            <div class="field">
                <div class="field-label">Message</div>
                <div class="message-content">{{ $messageContent ?? $message }}</div>
            </div>
        </div>

        <div class="footer">
            <p>Ce message a été envoyé depuis le formulaire de contact de <strong>{{ config('app.name', 'EasyVente') }}</strong></p>
            <p>{{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
</body>
</html>
