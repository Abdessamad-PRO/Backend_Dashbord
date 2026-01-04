<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\TaskCancellationRequest;
use App\Models\Task;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;

class TaskCancellationRequestController extends Controller
{
 public function store(Request $request, $taskId)
{
    $request->validate([ 
        'name' => 'required|string|max:255',
        'reason' => 'required|string|max:1000', 
    ]);

    // Vérifier que la tâche existe et appartient bien à l'utilisateur
    $task = Task::findOrFail($taskId);
    if ($task->assigned_to !== Auth::id()) {
        return response()->json(['message' => 'Vous ne pouvez pas demander l\'annulation de cette tâche.'], 403);
    }

    // Vérifier qu'il n'existe pas déjà une demande en attente pour cette tâche
    $existingRequest = TaskCancellationRequest::where('task_id', $taskId)
        ->where('status', 'pending')
        ->first();

    if ($existingRequest) {
        return response()->json(['message' => 'Une demande d\'annulation est déjà en attente pour cette tâche.'], 400);
    }
    // $cancellation = TaskCancellationRequest::findOrFail($taskId);
    $task = Task::findOrFail($taskId);
    // Créer la demande
    $cancelRequest = TaskCancellationRequest::create([
        'task_id' => $taskId,
        'user_id' => Auth::id(),
        'name' => $request->name,
        'reason' => $request->reason,
        'status' => 'pending',
    ]);

    // Notifier le manager
    $manager = User::find($task->project->manager_id);
    if ($manager) { 
        AppNotification::create([
            'user_id' => $manager->id,
            'title' => 'Nouvelle demande d\'annulation d\'une tâche',
            'message' => Auth::user()->name . ' a demandé l\'annulation de la tâche "' . $task->name . '".',
            'type' => 'task_cancellation_request',
            'read' => false,
            'data' => [ 
                'request_id' => $cancelRequest->id,
                'task_id' => $task->id,
                'task_name' => $task->name, 
                'user_id' => Auth::id(), 
                'user_name' => Auth::user()->name . ' ' . Auth::user()->prenom,
                'reason' => $request->reason,
            ], 
            'action_url' => '/manager/task-cancellation-requests/' . $cancelRequest->id,
        ]);
    }

    return response()->json([
        'message' => 'Demande d\'annulation envoyée avec succès.',
        'request' => $cancelRequest,
        'task_name' => $task->name,
    ]);
} 

public function approve($id)
{ 
    $request = TaskCancellationRequest::findOrFail($id);
    $request->update([
        'status' => 'approved',
        'processed_by' => Auth::id(),
        'processed_at' => now(),
    ]);

    $request->task->delete();

    AppNotification::create([
        'user_id' => $request->user_id,
        'title' => 'Demande d’annulation approuvée', 
        'message' => "Votre tâche a été annulée avec succès.",
        'type' => 'task_cancellation_approved',
        'read' => false,
    ]);

    return response()->json(['message' => 'Tâche annulée.']);
}
 
public function reject(Request $request, $id)
{ 
    $request->validate(['rejection_reason' => 'required|string|max:1000']); 

    $cancellation = TaskCancellationRequest::findOrFail($id);
    $cancellation->update([
        'status' => 'rejected',
        'processed_by' => Auth::id(),
        'processed_at' => now(),
        'rejection_reason' => $request->rejection_reason,
    ]);
   
    AppNotification::create([
        'user_id' => $cancellation->user_id, 
        'title' => 'Votre demande d’annulation de la tâche ' . $cancellation->task->name . ' a été rejetée.',
        'message' => 'Votre demande d’annulation a été rejetée.' ,
        'type' => 'task_cancellation_rejected',
        'read' => false, 
        'data' => [ 
                'request_id' => $cancellation->id, 
                'rejection_reason' => $request->rejection_reason,
                
                
            ],
        
    ]); 

    return response()->json(['message' => 'Demande rejetée.']);
}


}


