<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande rejetée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            color: #f44336;
        }
        .icon {
            font-size: 64px;
            color: #f44336;
            margin-bottom: 20px;
        }
        .user-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
            text-align: left;
        }
        .rejection-reason {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 5px;
            text-align: left;
            border-left: 4px solid #f44336;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✗</div>
        
        <div class="header">
            <h1>Demande rejetée</h1>
            <p>La demande d'inscription a été rejetée et l'utilisateur a été notifié.</p>
        </div>
        
        <div class="user-info">
            <h3>Informations de l'utilisateur</h3>
            <p><strong>Nom:</strong> {{ $request->name }}</p>
            <p><strong>Prénom:</strong> {{ $request->prenom }}</p>
            <p><strong>Email:</strong> {{ $request->email }}</p>
            <p><strong>Rôle demandé:</strong> {{ $request->role }}</p>
        </div>
        
        <div class="rejection-reason">
            <h3>Motif du rejet</h3>
            <p>{{ $request->rejection_reason }}</p>
        </div>
        
        <p>Un email a été envoyé à l'utilisateur pour l'informer du rejet de sa demande.</p>
        
        <div class="footer">
            <p>Cette action a été effectuée automatiquement suite à votre décision.</p>
            <p>&copy; {{ date('Y') }} TASKAURA. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
