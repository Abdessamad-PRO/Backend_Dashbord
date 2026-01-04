<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rejeter la demande d'inscription</title>
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
        }
        .header {
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            color: #f44336;
        }
        .user-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            min-height: 100px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-right: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rejeter la demande d'inscription</h1>
            <p>Veuillez indiquer la raison du rejet de cette demande d'inscription.</p>
        </div>
        
        <div class="user-info">
            <h3>Informations de l'utilisateur</h3>
            <p><strong>Nom:</strong> {{ $request->name }}</p>
            <p><strong>Prénom:</strong> {{ $request->prenom }}</p>
            <p><strong>Email:</strong> {{ $request->email }}</p>
            <p><strong>Rôle demandé:</strong> {{ $request->role }}</p>
            <p><strong>Date de la demande:</strong> {{ $request->created_at->format('d/m/Y H:i') }}</p>
        </div>
        
        <div id="error-message" class="alert alert-danger" style="display: none;"></div>
        
        <form id="reject-form" action="{{ url('/api/admin/reject-registration-direct/' . $request->id . '/' . $token) }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="reason">Motif du rejet</label>
                <textarea id="reason" name="reason" required placeholder="Veuillez indiquer la raison pour laquelle cette demande est rejetée..."></textarea>
            </div>
            
            <div style="text-align: right;">
                <a href="{{ url('/api/admin/approve-registration-direct/' . $request->id . '/' . $token) }}" class="btn btn-secondary">Approuver à la place</a>
                <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
            </div>
        </form>
        
        <div class="footer">
            <p>Cette action est irréversible. L'utilisateur sera notifié du rejet de sa demande.</p>
            <p>&copy; {{ date('Y') }} TASKAURA. Tous droits réservés.</p>
        </div>
    </div>

    <script>
        document.getElementById('reject-form').addEventListener('submit', function(e) {
            const reason = document.getElementById('reason').value.trim();
            
            if (!reason) {
                e.preventDefault();
                document.getElementById('error-message').textContent = 'Veuillez indiquer un motif de rejet.';
                document.getElementById('error-message').style.display = 'block';
            }
        });
    </script>
</body>
</html>
