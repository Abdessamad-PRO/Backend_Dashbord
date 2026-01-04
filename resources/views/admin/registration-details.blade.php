<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la demande d'inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .btn-success, .btn-danger {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Détails de la demande d'inscription</h4>
                    </div>
                    <div class="card-body">
                        <div id="loading" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p>Chargement des détails...</p>
                        </div>
                        
                        <div id="registration-details" style="display: none;">
                            <div class="mb-3">
                                <h5>Informations personnelles</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nom:</strong> <span id="name"></span></p>
                                        <p><strong>Prénom:</strong> <span id="prenom"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Email:</strong> <span id="email"></span></p>
                                        <p><strong>Téléphone:</strong> <span id="phone"></span></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h5>Détails de la demande</h5>
                                <p><strong>Rôle demandé:</strong> <span id="role"></span></p>
                                <p><strong>Date de la demande:</strong> <span id="created-at"></span></p>
                            </div>
                            
                            <div class="mb-3" id="rejection-form" style="display: none;">
                                <h5>Motif de rejet</h5>
                                <textarea id="rejection-reason" class="form-control" rows="3" placeholder="Veuillez indiquer le motif du rejet"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button id="approve-btn" class="btn btn-success">Approuver</button>
                                <button id="toggle-reject-btn" class="btn btn-outline-danger">Rejeter</button>
                                <button id="confirm-reject-btn" class="btn btn-danger" style="display: none;">Confirmer le rejet</button>
                            </div>
                        </div>
                        
                        <div id="error-message" class="alert alert-danger mt-3" style="display: none;"></div>
                        <div id="success-message" class="alert alert-success mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Récupérer l'ID de la demande d'inscription depuis l'URL
        const urlParams = new URLSearchParams(window.location.pathname);
        const registrationId = window.location.pathname.split('/').pop();
        
        // Fonction pour formater la date
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleDateString('fr-FR', options);
        }
        
        // Charger les détails de la demande d'inscription
        async function loadRegistrationDetails() {
            try {
                const response = await fetch(`/api/admin/registration/${registrationId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des détails');
                }
                
                const data = await response.json();
                const registration = data.data;
                
                // Afficher les détails
                document.getElementById('name').textContent = registration.name;
                document.getElementById('prenom').textContent = registration.prenom;
                document.getElementById('email').textContent = registration.email;
                document.getElementById('phone').textContent = registration.phone || 'Non spécifié';
                document.getElementById('role').textContent = registration.role;
                document.getElementById('created-at').textContent = formatDate(registration.created_at);
                
                // Masquer le chargement et afficher les détails
                document.getElementById('loading').style.display = 'none';
                document.getElementById('registration-details').style.display = 'block';
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error-message').textContent = error.message;
                document.getElementById('error-message').style.display = 'block';
            }
        }
        
        // Approuver la demande d'inscription
        async function approveRegistration() {
            try {
                document.getElementById('approve-btn').disabled = true;
                document.getElementById('toggle-reject-btn').disabled = true;
                document.getElementById('confirm-reject-btn').disabled = true;
                
                const response = await fetch(`/api/admin/approve-registration/${registrationId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Erreur lors de l\'approbation de la demande');
                }
                
                const data = await response.json();
                document.getElementById('success-message').textContent = data.message;
                document.getElementById('success-message').style.display = 'block';
                
                // Désactiver les boutons après l'approbation
                document.getElementById('approve-btn').style.display = 'none';
                document.getElementById('toggle-reject-btn').style.display = 'none';
                document.getElementById('confirm-reject-btn').style.display = 'none';
            } catch (error) {
                document.getElementById('error-message').textContent = error.message;
                document.getElementById('error-message').style.display = 'block';
                
                document.getElementById('approve-btn').disabled = false;
                document.getElementById('toggle-reject-btn').disabled = false;
                document.getElementById('confirm-reject-btn').disabled = false;
            }
        }
        
        // Rejeter la demande d'inscription
        async function rejectRegistration() {
            try {
                const rejectionReason = document.getElementById('rejection-reason').value.trim();
                
                if (!rejectionReason) {
                    throw new Error('Veuillez indiquer un motif de rejet');
                }
                
                document.getElementById('approve-btn').disabled = true;
                document.getElementById('toggle-reject-btn').disabled = true;
                document.getElementById('confirm-reject-btn').disabled = true;
                
                const response = await fetch(`/api/admin/reject-registration/${registrationId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify({ reason: rejectionReason })
                });
                
                if (!response.ok) {
                    throw new Error('Erreur lors du rejet de la demande');
                }
                
                const data = await response.json();
                document.getElementById('success-message').textContent = data.message;
                document.getElementById('success-message').style.display = 'block';
                
                // Désactiver les boutons après le rejet
                document.getElementById('approve-btn').style.display = 'none';
                document.getElementById('toggle-reject-btn').style.display = 'none';
                document.getElementById('confirm-reject-btn').style.display = 'none';
                document.getElementById('rejection-form').style.display = 'none';
            } catch (error) {
                document.getElementById('error-message').textContent = error.message;
                document.getElementById('error-message').style.display = 'block';
                
                document.getElementById('approve-btn').disabled = false;
                document.getElementById('toggle-reject-btn').disabled = false;
                document.getElementById('confirm-reject-btn').disabled = false;
            }
        }
        
        // Initialiser la page
        document.addEventListener('DOMContentLoaded', function() {
            // Charger les détails de la demande
            loadRegistrationDetails();
            
            // Gestionnaires d'événements pour les boutons
            document.getElementById('approve-btn').addEventListener('click', approveRegistration);
            
            document.getElementById('toggle-reject-btn').addEventListener('click', function() {
                document.getElementById('toggle-reject-btn').style.display = 'none';
                document.getElementById('confirm-reject-btn').style.display = 'block';
                document.getElementById('rejection-form').style.display = 'block';
            });
            
            document.getElementById('confirm-reject-btn').addEventListener('click', rejectRegistration);
        });
    </script>
</body>
</html>
