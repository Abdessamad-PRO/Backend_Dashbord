<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Définir votre mot de passe</title>
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
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4a6cf7;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #666;
        }
        .submit-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4a6cf7;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .submit-btn:hover {
            background-color: #3a5cd7;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Définir votre mot de passe</h1>
            <p>Veuillez créer un nouveau mot de passe pour votre compte.</p>
        </div>
        
        <div id="success-message" class="alert alert-success" style="display: none;"></div>
        <div id="error-message" class="alert alert-danger" style="display: none;"></div>
        
        <form id="reset-password-form">
            <input type="hidden" id="token" value="{{ $token }}">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" value="{{ $email }}" readonly>
            </div>
            
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <div class="password-container">
                    <input type="password" id="password" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
                </div>
                <div class="password-requirements">
                    Le mot de passe doit contenir au moins 8 caractères.
                </div>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <div class="password-container">
                    <input type="password" id="password_confirmation" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility('password_confirmation')">👁️</span>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Définir le mot de passe</button>
        </form>
    </div>

    <script>
        // Fonction pour basculer la visibilité du mot de passe
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }
        
        // Gestionnaire de soumission du formulaire
        document.getElementById('reset-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const token = document.getElementById('token').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            
            // Vérification des mots de passe
            if (password !== passwordConfirmation) {
                document.getElementById('error-message').textContent = 'Les mots de passe ne correspondent pas.';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            if (password.length < 8) {
                document.getElementById('error-message').textContent = 'Le mot de passe doit contenir au moins 8 caractères.';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            try {
                // Masquer les messages d'erreur précédents
                document.getElementById('error-message').style.display = 'none';
                
                // Désactiver le bouton pendant la soumission
                const submitBtn = document.querySelector('.submit-btn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Traitement en cours...';
                
                // Envoyer la requête
                const response = await fetch('/api/set-initial-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        token: token,
                        email: email,
                        password: password,
                        password_confirmation: passwordConfirmation
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Une erreur est survenue lors de la définition du mot de passe.');
                }
                
                // Afficher le message de succès
                document.getElementById('reset-password-form').style.display = 'none';
                document.getElementById('success-message').textContent = 'Votre mot de passe a été défini avec succès. Vous pouvez maintenant vous connecter à l\'application.';
                document.getElementById('success-message').style.display = 'block';
                
            } catch (error) {
                // Réactiver le bouton
                const submitBtn = document.querySelector('.submit-btn');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Définir le mot de passe';
                
                // Afficher l'erreur
                document.getElementById('error-message').textContent = error.message;
                document.getElementById('error-message').style.display = 'block';
            }
        });
    </script>
</body>
</html>
