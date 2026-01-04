<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation de mot de passe</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f0f8ff;
        }
        .email-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .title {
            color: #1a56db;
            font-size: 24px;
            margin-top: 20px;
        }
        .content {
            color: #495057;
            line-height: 1.8;
        }
        .verification-code {
            background-color: #e3f2fd;
            padding: 25px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #1a56db;
            margin: 30px 0;
            border-radius: 8px;
            letter-spacing: 10px;
        }
        .footer {
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ asset('images/logo-taskaura.png') }}" alt="Taskaura Logo">
        </div>
        
        <div class="title">
            <h2>Réinitialisation de mot de passe</h2>
        </div>
        
        <div class="content">
            <p>Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
            <p>Votre code de vérification est :</p>
            
            <div class="verification-code">
                {{ $code }}
            </div>
            
            <p>Ce code est valable pendant 15 minutes. Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action n'est requise.</p>
            <p>Cordialement,<br>Authentification</p>
            <p>Si vous rencontrez des problèmes avec le bouton ci-dessus, copiez et collez le code manuellement.</p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Taskaura. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>