<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeleteAccountRequest;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DeleteAccountRequestController extends Controller
{
    /**
     * Créer une nouvelle demande de suppression de compte
     */
    public function requestAccountDeletion(Request $request)
    { 
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $existingRequest = DeleteAccountRequest::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'Vous avez déjà une demande de suppression de compte en attente.',
            ], 400);
        }

        $deleteRequest = DeleteAccountRequest::create([
            'user_id' => Auth::id(), 
            'reason' => $request->reason,
            'status' => 'pending',
        ]); 

        $admins = User::where('role', 'admin')->get();
        $currentUser = Auth::user();  

        foreach ($admins as $admin) { 
            AppNotification::create([
                'user_id' => $admin->id,
                'title' => 'Nouvelle demande de suppression de compte', 
                'message' => $currentUser->name . ' ' . $currentUser->prenom . ' (' . $currentUser->email . ') a demandé la suppression de son compte.',
                'type' => 'delete_account_request', 
                'read' => false,
                'data' => [ 
                    'request_id' => $deleteRequest->id,
                    'user_id' => Auth::id(),
                    'user_first_name' => $currentUser->prenom,
                    'user_last_name' => $currentUser->name,
                    'user_email' => $currentUser->email,
                    'user_role' => $currentUser->role,
                    'deletion_reason' => $request->reason, ////  modif ici 
                ], 
                'action_url' => '/admin/delete-account-requests/' . $deleteRequest->id,
            ]); 
        } 

        return response()->json([ 
            'message' => 'Votre demande de suppression de compte a été envoyée à l\'administrateur.',
            'request' => $deleteRequest,
        ]);
    } 

    /**
     * Approuver une demande de suppression de compte
     */
    public function approveDeletion(Request $request, $requestId)
    { 
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $deleteRequest = DeleteAccountRequest::findOrFail($requestId);

        if ($deleteRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 400);
        }

        $user = $deleteRequest->user;
        
        // Mettre à jour le statut de la demande
        $deleteRequest->update([
            'status' => 'approved',
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        // Créer une notification pour l'utilisateur avant suppression 
        AppNotification::create([
            'user_id' => $deleteRequest->user_id,
            'title' => 'Votre demande de suppression de compte a été approuvée',
            'message' => 'Votre compte a été supprimé avec succès.',
            'type' => 'account_deleted',
            'read' => false, 
            'data' => [
                'request_id' => $requestId,
                'processed_by' => Auth::user()->name . ' ' . Auth::user()->prenom,
            ],
        ]);

        // Supprimer le compte de l'utilisateur
        $user->delete();

        return response()->json([
            'message' => 'La demande de suppression de compte a été approuvée et le compte a été supprimé.',
            'request' => $deleteRequest,
        ]);
    }

    /**
     * Rejeter une demande de suppression de compte
     */
    public function rejectDeletion(Request $request, $requestId)
    { 
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $deleteRequest = DeleteAccountRequest::findOrFail($requestId);

        if ($deleteRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 400);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        // Mettre à jour le statut de la demande
        $deleteRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]); 

        // Créer une notification pour l'utilisateur
        AppNotification::create([
            'user_id' => $deleteRequest->user_id,
            'title' => 'Votre demande de suppression de compte a été rejetée',
            'message' => 'Votre demande de suppression du compte a été rejetée. Raison: ' . $request->rejection_reason,
            'type' => 'account_deletion_rejected',
            'read' => false,
            'data' => [ 
                'request_id' => $requestId,
                'rejection_reason' => $request->rejection_reason,
                'processed_by' => Auth::user()->name . ' ' . Auth::user()->prenom,
            ],
        ]);

        return response()->json([
            'message' => 'La demande de suppression de compte a été rejetée.',
            'request' => $deleteRequest,
        ]);
    }

    // ... (garder les autres méthodes existantes)
    
    public function getAllRequests()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $requests = DeleteAccountRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function getPendingRequests()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $requests = DeleteAccountRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function getRequest($id)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $request = DeleteAccountRequest::with('user')
            ->findOrFail($id);

        return response()->json($request);
    }

    public function getMyRequestStatus()
    {
        $request = DeleteAccountRequest::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$request) {
            return response()->json([
                'has_request' => false,
                'message' => 'Vous n\'avez pas de demande de suppression de compte.',
            ]);
        }

        return response()->json([
            'has_request' => true,
            'request' => $request,
            'message' => $this->getStatusMessage($request->status),
        ]);
    }

    private function getStatusMessage($status)
    {
        switch ($status) {
            case 'pending':
                return 'Votre demande de suppression de compte est en attente d\'approbation par l\'administrateur.';
            case 'approved':
                return 'Votre demande de suppression de compte a été approuvée. Votre compte sera supprimé prochainement.';
            case 'rejected':
                return 'Votre demande de suppression de compte a été rejetée par l\'administrateur.';
            default:
                return 'Statut de la demande inconnu.';
        }
    }
}