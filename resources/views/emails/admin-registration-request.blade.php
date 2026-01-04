<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande d'inscription</title>
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
        .user-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
        }
        .approve-button {
            background-color: #4CAF50;
            color: white;
        }
        .reject-button {
            background-color: #f44336;
            color: white;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nouvelle demande d'inscription</h1>
        </div>
        
        <div class="content">
            <p>Bonjour Admin,</p>
            
            <p>Une nouvelle demande d'inscription a été soumise et nécessite votre approbation.</p>
            
            <div class="user-info">
                <h3>Informations de l'utilisateur</h3>
                <p><strong>Nom:</strong> {{ $request->name }}</p>
                <p><strong>Prénom:</strong> {{ $request->prenom }}</p>
                <p><strong>Email:</strong> {{ $request->email }}</p>
                <p><strong>Rôle demandé:</strong> {{ $request->role }}</p>
                <p><strong>Téléphone:</strong> {{ $request->phone ?? 'Non spécifié' }}</p>
                <p><strong>Date de la demande:</strong> {{ $request->created_at->format('d/m/Y H:i') }}</p>
            </div>
            
            <p>Veuillez approuver ou rejeter cette demande d'inscription:</p>
            
            <div class="button-container">
                <a href="{{ url('/api/admin/approve-registration-direct/' . $request->id . '/' . $approvalToken) }}" class="button approve-button">Approuver</a>
                <a href="{{ url('/api/admin/reject-registration-form/' . $request->id . '/' . $approvalToken) }}" class="button reject-button">Rejeter</a>
            </div>
            
            <p style="margin-top: 30px;">Merci d'utiliser TASKAURA!</p>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} TASKAURA. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
