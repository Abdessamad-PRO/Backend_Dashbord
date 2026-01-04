<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\AppNotification;
use Carbon\Carbon;
class TaskController extends Controller
{
    /**
     * Display a listing of the tasks for a project.
     */
    public function index($projectId)
    {  
        $project = Project::find($projectId);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        // Vérifier les permissions
        $user = Auth::user();
        
        // Admin peut voir toutes les tâches
        if ($user->isAdmin()) {
            // Accès autorisé
        }
        // Manager ne peut voir que les tâches de ses propres projets
        else if ($user->isManager() && $project->manager_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir les tâches de ce projet'
            ], 403);
        }
        // Utilisateur simple ne peut voir que les tâches qui lui sont assignées
        else if (!$user->isManager() && !$user->isAdmin()) {
            $tasks = Task::where('project_id', $projectId)
                        ->where('assigned_to', $user->id)
                        ->with('assignedUser')
                        ->get();
                        
            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        }

        // Pour admin et manager du projet
        $tasks = Task::where('project_id', $projectId)
                    ->with('assignedUser')
                    ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request, $projectId)
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un manager peut ajouter des tâches'
            ], 403);
        }

        $project = Project::find($projectId);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        // Vérifier que l'utilisateur est le manager du projet
        $user = Auth::user();
        if ($project->manager_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à ajouter des tâches à ce projet'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'previous_task_id' => 'nullable|exists:tasks,id',
        ]);
            $startDate = $request->start_date;

            if ($request->previous_task_id) {
                 $previousTask = Task::find($request->previous_task_id);
                if ($previousTask && $previousTask->end_date) {
                    $startDate = \Carbon\Carbon::parse($previousTask->end_date)->addDay()->format('Y-m-d');
                }
            }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date'=>$startDate,   //modif ici 
            'end_date' => $request->end_date,
            'status' => 'en_attente',
            'project_id' => $projectId,
            'assigned_to' => $request->assigned_to,
            'previous_task_id' => $request->previous_task_id,
        ]); 

        // Charger l'utilisateur assigné pour la réponse
        $task->load('assignedUser');

        // Envoyer une notification à l'utilisateur assigné si une assignation est spécifiée
        if ($request->assigned_to) {
            $this->notifyUserAboutTask($task, $project, 'assigned');
        } 

        return response()->json([
            'success' => true,
            'message' => 'Tâche créée avec succès',
            'data' => $task
        ], 201); 
    }

    /**
     * Display the specified task.
     */
    public function show($projectId, $taskId)
    {
        $task = Task::where('project_id', $projectId)
                    ->where('id', $taskId)
                    ->with('assignedUser', 'project')
                    ->first();
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }

        // Vérifier les permissions
        $user = Auth::user();
        
        // Admin peut voir toutes les tâches
        if ($user->isAdmin()) {
            // Accès autorisé
        }
        // Manager ne peut voir que les tâches de ses propres projets
        else if ($user->isManager() && $task->project->manager_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir cette tâche'
            ], 403);
        }
        // Utilisateur simple ne peut voir que les tâches qui lui sont assignées 
        else if (!$user->isManager() && !$user->isAdmin() && $task->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir cette tâche'
            ], 403);
        } 

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, $projectId, $taskId)
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un manager peut modifier une tâche'
            ], 403);
        }

        $task = Task::where('project_id', $projectId)
                    ->where('id', $taskId)
                    ->first();
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }

        // Vérifier que l'utilisateur est le manager du projet
        $user = Auth::user();
        $project = Project::find($projectId);
        
        if ($project->manager_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette tâche'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'end_date' => 'nullable|date',
            'status' => 'sometimes|required|in:en_attente,en_cours,terminé',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'assignation a changé
        $oldAssignedTo = $task->assigned_to;
        $newAssignedTo = $request->assigned_to;

        $task->update($request->only([
            'name', 'description', 'end_date', 'status', 'assigned_to'
        ]));

        // Recharger la tâche avec ses relations
        $task->load('assignedUser', 'project');

        // Envoyer une notification si l'assignation a changé
        if ($newAssignedTo && $oldAssignedTo !== $newAssignedTo) {
            $this->notifyUserAboutTask($task, $project, 'assigned');
        }

        // Envoyer une notification si le statut a changé
        if ($request->has('status') && $request->status !== $task->getOriginal('status')) {
            if ($task->assigned_to) {
                $this->notifyUserAboutTask($task, $project, 'status_updated');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tâche mise à jour avec succès',
            'data' => $task
        ]);
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy($projectId, $taskId)
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un manager peut supprimer une tâche'
            ], 403);
        }

        $task = Task::where('project_id', $projectId)
                    ->where('id', $taskId)
                    ->first();
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }

        // Vérifier que l'utilisateur est le manager du projet
        $user = Auth::user();
        $project = Project::find($projectId);
        
        if ($project->manager_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette tâche'
            ], 403);
        }

        // Envoyer une notification à l'utilisateur assigné si la tâche est supprimée
        if ($task->assigned_to) {
            $this->notifyUserAboutTask($task, $project, 'deleted');
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tâche supprimée avec succès'
        ]);
    }

    /**
     * Envoyer une notification à l'utilisateur assigné à une tâche
     * 
     * @param Task $task La tâche concernée
     * @param Project $project Le projet associé à la tâche
     * @param string $action L'action effectuée (assigned, status_updated, deleted)
     * @return void
     */
    private function notifyUserAboutTask($task, $project, $action)
    {
        // Vérifier que la tâche a un utilisateur assigné
        if (!$task->assigned_to) {
            return;
        }

        $assignedUser = User::find($task->assigned_to);
        if (!$assignedUser) {
            return;
        }

        $title = '';
        $message = ''; 
        $type = '';

        switch ($action) {
            case 'assigned':
                $title = 'Nouvelle tâche assignée';
                $message = 'Vous avez été assigné à la tâche "' . $task->name . '" dans le projet "' . $project->name . '".';
                $type = 'task_assigned';
                break;
            case 'status_updated':
                $title = 'Statut de tâche mis à jour';
                $message = 'Le statut de la tâche "' . $task->name . '" a été mis à jour à "' . $task->status . '".';
                $type = 'task_status_updated';
                break;
            case 'deleted':
                $title = 'Tâche supprimée';
                $message = 'La tâche "' . $task->name . '" à laquelle vous étiez assigné a été supprimée.';
                $type = 'task_deleted';
                break;
            default:
                return;
        }

        // Créer la notification
        AppNotification::create([
            'user_id' => $assignedUser->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'read' => false,
            'data' => [
                'task_id' => $task->id, 
                'project_id' => $project->id,
                'task_name' => $task->name,
                'project_name' => $project->name,
                'task_status' => $task->status,
                'task_description' => $task->description,
                'task_end_date' => $task->end_date ? $task->end_date->format('Y-m-d') : null,
            ],
            'action_url' => '/projects/' . $project->id . '/tasks/' . $task->id,
        ]);
    }

     public function getEmployees()
{
    // Récupère tous les utilisateurs avec le rôle 'user' (employé)
    $employees = User::where('role', 'user')->get();

    return response()->json([
        'success' => true,
        'data' => $employees 
    ]);
} 


public function getTasksForManager()
{
    $managerId = auth()->id();

    // Récupérer les tâches dont le projet est géré par ce manager
    $tasks = Task::whereHas('project', function ($query) use ($managerId) {
        $query->where('manager_id', $managerId);
    })->with(['project', 'assignedUser'])->get(); 

    return response()->json([
        'message' => 'Tâches du manager récupérées avec succès.',
        'data' => $tasks
    ]);
}

public function getTasksForEmployee(Request $request)
{
    $user = $request->user();

    $tasks = Task::with(['project', 'previousTask'])
                ->where('assigned_to', $user->id)
                ->get();

    return response()->json(['data' => $tasks]);
}
public function getEmployeesWithStats()
{
    // Récupérer uniquement les utilisateurs de type "user" (employés)
    $employees = User::where('role', 'user')->get();

    $results = $employees->map(function ($employee) {
        // Récupérer les tâches assignées à cet employé
        $tasks = Task::with('project')
            ->where('assigned_to', $employee->id)
            ->get();

        // Compter le nombre de projets distincts associés à ces tâches
        $projectCount = $tasks->pluck('project_id')->unique()->count();

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'prenom' => $employee->prenom,
            'email' => $employee->email,
            'departement' => $employee->departement,
            'task_count' => $tasks->count(),
            'project_count' => $projectCount,
        ];
    });

    return response()->json(['data' => $results]);
} 

public function getManagersStatsFromTasks()
{
    // On commence par récupérer tous les managers distincts qui ont des projets avec des tâches
    $managers = User::where('role', 'manager')->get();

    $managersWithStats = $managers->map(function ($manager) {
        // Récupère tous les projets du manager
        $projectIds = Project::where('manager_id', $manager->id)->pluck('id');

        // Nombre total de projets
        $projectsCount = $projectIds->count();

        // Nombre d'employés distincts assignés à des tâches dans ces projets
        $employeesCount = Task::whereIn('project_id', $projectIds)
            ->whereNotNull('assigned_to')
            ->distinct('assigned_to')
            ->count('assigned_to'); 

        return [
            'id' => $manager->id,
            'first_name' => $manager->prenom,
            'last_name' => $manager->name,
            'email' => $manager->email,
            'departement' => $manager->departement,
            'projects_count' => $projectsCount,
            'team_members_count' => $employeesCount,
        ];
    });

    return response()->json([
        'data' => $managersWithStats
    ]);
}

}
