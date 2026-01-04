<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     */
   public function index()
{ 
    $user = Auth::user(); 

    if ($user->isAdmin()) {
        // Admin : tous les projets avec manager + tâches + utilisateur assigné à chaque tâche
        $projects = Project::with(['manager', 'tasks.assignedUser'])->get();

    } else if ($user->isManager()) { 
        // Manager : ses projets avec tâches + utilisateur assigné à chaque tâche
        $projects = Project::where('manager_id', $user->id)
            ->with(['manager', 'tasks.assignedUser'])
            ->get();  

    } else {
        // Employé : projets où il a des tâches
        $projectIds = Task::where('assigned_to', $user->id)
            ->pluck('project_id')
            ->unique();

        $projects = Project::whereIn('id', $projectIds)
            ->with(['manager', 'tasks' => function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->with('assignedUser');
            }])
            ->get();
    }

    return response()->json([
        'success' => true,
        'data' => $projects
    ]);
}

    /**
     * Store a newly created project in storage. 
     */
    public function store(Request $request) 
    { 
        // Vérifier que l'utilisateur est un manager uniquement
        if (!Auth::user()->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un manager peut créer un projet'
            ], 403);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'tasks' => 'nullable|array',
            'tasks.*.name' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.due_date' => 'nullable|date',
            'tasks.*.assigned_to' => 'nullable|exists:users,id', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Créer le projet avec le manager actuel comme responsable
        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'en_attente',
            'manager_id' => Auth::id(), // Le manager qui crée le projet en est le responsable
        ]); 

        // Créer les tâches associées si elles existent 
        if ($request->has('tasks') && is_array($request->tasks)) {
            foreach ($request->tasks as $taskData) {
                $task = new Task([
                    'name' => $taskData['name'],
                    'description' => $taskData['description'] ?? null,
                    'end_date' => $taskData['end_date'] ?? null,
                    'status' => 'pending',
                    'assigned_to' => $taskData['assigned_to'] ?? null,
                ]);
                
                $project->tasks()->save($task);
            }
        }

        // Charger le projet avec ses tâches et son manager pour la réponse
        $project->load(['tasks', 'manager']);

        return response()->json([
            'success' => true,
            'message' => 'Projet créé avec succès',
            'data' => $project
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show($id)
    {
        $project = Project::with(['tasks', 'manager'])->find($id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        // Vérifier les permissions
        $user = Auth::user();
        
        // Admin peut voir tous les projets
        if ($user->isAdmin()) {
            // Accès autorisé
        }
        // Manager ne peut voir que ses propres projets
        else if ($user->isManager() && $project->manager_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir ce projet'
            ], 403);
        }
        // Utilisateur simple ne peut voir que les projets auxquels il est assigné
        else if (!$user->isManager() && !$user->isAdmin() && 
                !Task::where('project_id', $project->id)->where('assigned_to', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir ce projet'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, $id)
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un manager peut modifier un projet'
            ], 403);
        }

        $project = Project::find($id);
        
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
                'message' => 'Vous n\'êtes pas autorisé à modifier ce projet'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'sometimes|required|in:en_attente,en_cours,terminé',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Mettre à jour le projet
        $project->update($request->only([
            'name', 'description', 'start_date', 'end_date', 'status'
        ]));

        // Recharger le projet avec ses relations
        $project->load(['tasks', 'manager']);

        return response()->json([
            'success' => true,
            'message' => 'Projet mis à jour avec succès',
            'data' => $project
        ]);
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy($id)
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un manager peut supprimer un projet'
            ], 403);
        }

        $project = Project::find($id);
        
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
                'message' => 'Vous n\'êtes pas autorisé à supprimer ce projet'
            ], 403);
        }

        // Supprimer le projet (les tâches seront supprimées automatiquement grâce à la contrainte onDelete('cascade'))
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
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

} 
