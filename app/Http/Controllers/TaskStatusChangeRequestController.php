<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskStatusChangeRequest;
use App\Models\Task;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;

class TaskStatusChangeRequestController extends Controller
{
 public function requestStatusChange(Request $request, $taskId)
{
    // Valider uniquement le statut demandé
    $request->validate([
        'requested_status' => 'required|in:en_attente,en_cours,terminé',
    ]); 

    $user = Auth::user();

    // Vérifier que la tâche existe et appartient à l'utilisateur
    $task = Task::with('project.manager')
        ->where('id', $taskId)
        ->where('assigned_to', $user->id)
        ->first();

    if (!$task) {
        return response()->json(['message' => 'Tâche non trouvée ou non assignée à cet utilisateur.'], 403);
    }

    // Vérifier s’il existe déjà une demande en attente
    $existing = TaskStatusChangeRequest::where([
        ['task_id', $task->id],
        ['user_id', $user->id],
        ['status', 'pending'],
    ])->first();

    if ($existing) {
        return response()->json(['message' => 'Une demande est déjà en attente pour cette tâche.'], 400);
    }

    // Créer la nouvelle demande
    $statusRequest = TaskStatusChangeRequest::create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'requested_status' => $request->requested_status,
    ]);

    // Créer la notification pour le manager
    $manager = $task->project->manager;

    if ($manager) {
        AppNotification::create([
            'user_id' => $manager->id,
            'title' => 'Demande de modification de statut',
            'message' => "{$user->name} {$user->prenom} souhaite modifier le statut de la tâche « {$task->name} » vers « {$request->requested_status} ».",
            'type' => 'task_status_change',
            'read' => false,
            'data' => [ 
                'request_id' => $statusRequest->id,
                'task_id' => $task->id,
                'task_name' => $task->name,
                'user_name' => $user->name . ' ' . $user->prenom,
                'requested_status' => $request->requested_status,
            ],
            'action_url' => '/manager/task-status-requests/' . $statusRequest->id,
        ]);
    }

    return response()->json([
        'message' => 'Demande de changement de statut envoyée avec succès.',
        'request' => $statusRequest,
    ]);
} 

public function approve($id)
{
    $statusRequest = TaskStatusChangeRequest::findOrFail($id);

    if ($statusRequest->status !== 'pending') {
        return response()->json(['message' => 'Demande déjà traitée.'], 400);
    }

    $statusRequest->update([
        'status' => 'approved',
        'processed_by' => Auth::id(),
        'processed_at' => now(),
    ]);

    $statusRequest->task->update([
        'status' => $statusRequest->requested_status,
    ]);

    AppNotification::create([
        'user_id' => $statusRequest->user_id,
        'title' => 'Statut de la tâche : ' . $statusRequest->task->name . ' est modifié avec succès',
        'message' => 'Le statut de votre tâche a été mis à jour par le manager.',
        'type' => 'task_status_approved',
        'read' => false,
    ]);

    return response()->json(['message' => 'Statut mis à jour.']);
}

public function reject($id)
{
    $statusRequest = TaskStatusChangeRequest::findOrFail($id);

    if ($statusRequest->status !== 'pending') {
        return response()->json(['message' => 'Demande déjà traitée.'], 400);
    }

    $statusRequest->update([
        'status' => 'rejected',
        'processed_by' => Auth::id(),
        'processed_at' => now(),
    ]);

    AppNotification::create([
        'user_id' => $statusRequest->user_id,
        'title' => 'Modification de statut rejetée par le manager',
        'message' => 'Votre demande de changement de statut a été rejetée.',
        'type' => 'task_status_rejected',
        'read' => false,
    ]);

    return response()->json(['message' => 'Demande rejetée.']);
}


}
