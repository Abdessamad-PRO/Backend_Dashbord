<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Définir votre mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Définir votre mot de passe</h4>
                    </div>
                    <div class="card-body">
                        <div id="loading" class="text-center" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p>Traitement en cours...</p>
                        </div>
                        
                        <form id="password-form">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" id="password" required>
                                    <span class="password-toggle" onclick="togglePasswordVisibility('password')">
                                        <i class="bi bi-eye"></i>
                                    </span>
                                </div>
                                <div class="form-text">
                                    Le mot de passe doit contenir au moins 8 caractères.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                                <div class="password-container">
                                    <input type="password" class="form-control" id="password_confirmation" required>
                                    <span class="password-toggle" onclick="togglePasswordVisibility('password_confirmation')">
                                        <i class="bi bi-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Définir le mot de passe</button>
                            </div>
                        </form>
                        
                        <div id="error-message" class="alert alert-danger mt-3" style="display: none;"></div>
                        <div id="success-message" class="alert alert-success mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"></script>
    <script>
        // Récupérer les paramètres de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        const email = urlParams.get('email');
        
        // Vérifier que le token et l'email sont présents
        if (!token || !email) {
            document.getElementById('password-form').style.display = 'none';
            document.getElementById('error-message').textContent = 'Lien invalide ou expiré. Veuillez contacter l\'administrateur.';
            document.getElementById('error-message').style.display = 'block';
        } else {
            // Remplir l'email
            document.getElementById('email').value = email;
        }
        
        // Fonction pour basculer la visibilité du mot de passe
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Changer l'icône
            const icon = input.nextElementSibling.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Soumettre le formulaire
        document.getElementById('password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;
            
            // Vérifier que les mots de passe correspondent
            if (password !== passwordConfirmation) {
                document.getElementById('error-message').textContent = 'Les mots de passe ne correspondent pas.';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            // Vérifier la longueur du mot de passe
            if (password.length < 8) {
                document.getElementById('error-message').textContent = 'Le mot de passe doit contenir au moins 8 caractères.';
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            
            try {
                // Masquer les messages d'erreur précédents
                document.getElementById('error-message').style.display = 'none';
                
                // Afficher le chargement
                document.getElementById('password-form').style.display = 'none';
                document.getElementById('loading').style.display = 'block';
                
                // Envoyer la requête
                const response = await fetch('/api/set-initial-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
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
                document.getElementById('loading').style.display = 'none';
                document.getElementById('success-message').textContent = data.message || 'Votre mot de passe a été défini avec succès. Vous pouvez maintenant vous connecter.';
                document.getElementById('success-message').style.display = 'block';
                
                // Rediriger vers la page de connexion après 3 secondes
                setTimeout(function() {
                    window.location.href = '/login';
                }, 3000);
                
            } catch (error) {
                // Afficher l'erreur
                document.getElementById('loading').style.display = 'none';
                document.getElementById('password-form').style.display = 'block';
                document.getElementById('error-message').textContent = error.message;
                document.getElementById('error-message').style.display = 'block';
            }
        });
    </script>
</body>
</html>
