<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre compte a été approuvé</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #4a6cf7;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .credentials {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
            border-left: 4px solid #4a6cf7;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4a6cf7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .warning {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Votre compte a été approuvé</h1>
        </div>
        
        <div class="content">
            <p>Bonjour {{ $user->prenom }},</p>
            
            <p>Nous sommes heureux de vous informer que votre demande d'inscription a été approuvée.</p>
            
            <p>Voici vos identifiants de connexion temporaires :</p>
            
            <div class="credentials">
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Mot de passe temporaire:</strong> {{ $password }}</p>
            </div>
            
            <p class="warning">Pour des raisons de sécurité, veuillez changer votre mot de passe dès votre première connexion en cliquant sur le bouton ci-dessous :</p>
            
            <div style="text-align: center;">
                <a href="{{ $setPasswordUrl }}" class="button">Définir mon mot de passe</a>
            </div>
            
            <p>Si le bouton ne fonctionne pas, vous pouvez copier et coller ce lien dans votre navigateur :</p>
            <p style="word-break: break-all; font-size: 12px;">{{ $setPasswordUrl }}</p>
            
            <p>Ce lien est valable pour une durée limitée. Si vous ne définissez pas votre mot de passe dans ce délai, vous devrez contacter l'administrateur.</p>
            
            <p>Merci de votre confiance et bienvenue dans TASKAURA !</p>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} TASKAURA. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
