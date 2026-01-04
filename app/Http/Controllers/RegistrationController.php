<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Notifications\AdminNewRegistrationNotification;
use App\Notifications\AccountApproved;
use App\Notifications\AccountRejected;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    /**
     * Enregistrer une nouvelle demande d'inscription
     */
    public function requestRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|unique:registration_requests',
            'role' => 'required|string|in:user,manager,admin',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Créer la demande d'inscription
        $registrationRequest = RegistrationRequest::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phone,
            'status' => 'pending',
        ]);

        // Notifier les administrateurs
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new AdminNewRegistrationNotification($registrationRequest));

        return response()->json(['message' => 'Demande d\'inscription envoyée avec succès.'], 200);
    } 

    /**
     * Récupérer la liste des demandes d'inscription en attente
     */
    public function getPendingRegistrations()
    {
        $this->authorize('viewAny', RegistrationRequest::class);

        $pendingRequests = RegistrationRequest::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $pendingRequests], 200);
    }

    /**
     * Approuver une demande d'inscription
     */
    public function approveRegistration($id)
    {
        $this->authorize('update', RegistrationRequest::class);

        $registrationRequest = RegistrationRequest::findOrFail($id);

        if ($registrationRequest->status !== 'pending') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        // Générer un mot de passe aléatoire
        $password = Str::random(10);
        
        // Générer un token pour la définition du mot de passe
        $token = Str::random(64);

        // Créer l'utilisateur
        $user = User::create([
            'name' => $registrationRequest->name,
            'prenom' => $registrationRequest->prenom, 
            'email' => $registrationRequest->email,
            'password' => Hash::make($password),
            'role' => $registrationRequest->role,
            'email_utilisateur' => $registrationRequest->email,
            'telephone' => $registrationRequest->phone,
        ]);

        // Mettre à jour le statut de la demande
        $registrationRequest->update([
            'status' => 'approved',
            'token' => $token,
        ]);

        // Envoyer l'email à l'utilisateur avec ses identifiants
        $user->notify(new AccountApproved($user, $password, $token));

        return response()->json(['message' => 'Demande approuvée avec succès.'], 200);
    }

    /**
     * Rejeter une demande d'inscription
     */
    public function rejectRegistration(Request $request, $id)
    {
        $this->authorize('update', RegistrationRequest::class);

        $registrationRequest = RegistrationRequest::findOrFail($id);

        if ($registrationRequest->status !== 'pending') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        // Mettre à jour le statut de la demande
        $registrationRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        // Envoyer l'email à l'utilisateur pour l'informer du rejet
        Notification::route('mail', $registrationRequest->email)
            ->notify(new AccountRejected($registrationRequest));

        return response()->json(['message' => 'Demande rejetée avec succès.'], 200);
    }

    /**
     * Définir le mot de passe initial
     */
    public function setInitialPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email|exists:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier que le token est valide
        $registrationRequest = RegistrationRequest::where('email', $request->email)
            ->where('token', $request->token)
            ->where('status', 'approved')
            ->first();

        if (!$registrationRequest) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 422);
        }

        // Mettre à jour le mot de passe de l'utilisateur
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Invalider le token
        $registrationRequest->update([
            'token' => null,
        ]);

        return response()->json(['message' => 'Mot de passe défini avec succès.'], 200);
    }

    /**
     * Récupérer les détails d'une demande d'inscription spécifique
     */
    public function getRegistrationDetails($id)
    {
        $this->authorize('view', RegistrationRequest::class);

        $registrationRequest = RegistrationRequest::findOrFail($id);

        return response()->json(['data' => $registrationRequest], 200);
    }

    /**
     * Approuver une demande d'inscription directement depuis l'email
     */
    public function approveRegistrationDirect($id, $token)
    {
        $registrationRequest = RegistrationRequest::where('id', $id)
            ->where('approval_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        // Générer un mot de passe aléatoire
        $password = Str::random(10);
        
        // Générer un token pour la définition du mot de passe
        $passwordToken = Str::random(64);

        // Créer l'utilisateur
        $user = User::create([
            'name' => $registrationRequest->name,
            'prenom' => $registrationRequest->prenom, 
            'email' => $registrationRequest->email,
            'password' => Hash::make($password),
            'role' => $registrationRequest->role,
            'email_utilisateur' => $registrationRequest->email,
            'telephone' => $registrationRequest->phone,
        ]);

        // Mettre à jour le statut de la demande
        $registrationRequest->update([
            'status' => 'approved',
            'token' => $passwordToken,
            'approval_token' => null, // Invalider le token d'approbation
        ]);

        // Envoyer l'email à l'utilisateur avec ses identifiants
        $user->notify(new AccountApproved($user, $password, $passwordToken));

        // Retourner une page HTML de confirmation
        return response()->view('emails.approval-success', [
            'request' => $registrationRequest
        ]);
    }

    /**
     * Afficher le formulaire de rejet d'une demande d'inscription
     */
    public function showRejectForm($id, $token)
    {
        $registrationRequest = RegistrationRequest::where('id', $id)
            ->where('approval_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        return response()->view('emails.reject-form', [
            'request' => $registrationRequest,
            'token' => $token
        ]);
    }

    /**
     * Rejeter une demande d'inscription directement depuis l'email
     */
    public function rejectRegistrationDirect(Request $request, $id, $token)
    {
        $registrationRequest = RegistrationRequest::where('id', $id)
            ->where('approval_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        // Mettre à jour le statut de la demande
        $registrationRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'approval_token' => null, // Invalider le token d'approbation
        ]);

        // Envoyer l'email à l'utilisateur pour l'informer du rejet
        Notification::route('mail', $registrationRequest->email)
            ->notify(new AccountRejected($registrationRequest));

        // Retourner une page HTML de confirmation
        return response()->view('emails.rejection-success', [
            'request' => $registrationRequest
        ]);
    }

    /**
     * Afficher le formulaire de définition du mot de passe
     */
    public function showSetPasswordForm($token)
    {
        $registrationRequest = RegistrationRequest::where('token', $token)
            ->where('status', 'approved')
            ->firstOrFail();

        return response()->view('emails.reset-password-form', [
            'token' => $token,
            'email' => $registrationRequest->email
        ]);
    }
}
