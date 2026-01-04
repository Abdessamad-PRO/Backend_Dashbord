<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExportController extends Controller
{
    /**
     * Export un projet spécifique avec ses utilisateurs et tâches en Excel
     *
     * @param int $projectId
     * @return \Illuminate\Http\Response
     */
    public function exportProject($projectId)
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json(['message' => 'Non autorisé. Seuls les managers peuvent exporter des projets.'], 403);
        }

        // Récupérer le projet avec ses relations
        $project = Project::with(['tasks.assignedUser', 'manager'])
            ->where('id', $projectId)
            ->where('manager_id', Auth::id())
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou vous n\'êtes pas autorisé à y accéder'], 404);
        }

        // Préparer les données pour l'export
        $data = [];
        
        // Ajouter les informations du projet
        $projectData = [
            'ID Projet' => $project->id,
            'Nom Projet' => $project->name,
            'Description' => $project->description,
            'Date Début' => $project->start_date,
            'Date Fin' => $project->end_date,
            'Manager' => $project->manager->name,
            'Tâche' => '',
            'Description Tâche' => '',
            'Assigné à' => '',
            'Statut Tâche' => ''
        ];
        $data[] = $projectData;

        // Ajouter les tâches
        foreach ($project->tasks as $task) {
            $taskData = [
                'ID Projet' => '',
                'Nom Projet' => '',
                'Description' => '',
                'Date Début' => '',
                'Date Fin' => '',
                'Manager' => '',
                'Tâche' => $task->name,
                'Description Tâche' => $task->description,
                'Assigné à' => $task->assignedUser->name,
                'Statut Tâche' => $task->status
            ];
            $data[] = $taskData;
        }

        // Générer le fichier Excel
        $filename = 'projet_' . $project->name . '_' . date('Y-m-d') . '.xlsx';
        return Excel::downloadFromCollection($data, $filename);
    }

    /**
     * Export tous les projets du manager connecté en Excel
     *
     * @return \Illuminate\Http\Response
     */
    public function exportAllProjects()
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json(['message' => 'Non autorisé. Seuls les managers peuvent exporter des projets.'], 403);
        }

        // Récupérer tous les projets du manager avec leurs relations
        $projects = Project::with(['tasks.assignedUser', 'manager'])
            ->where('manager_id', Auth::id())
            ->get();

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'Aucun projet trouvé'], 404);
        }

        // Préparer les données pour l'export
        $data = [];
        
        foreach ($projects as $project) {
            // Ajouter les informations du projet
            $projectData = [
                'ID Projet' => $project->id,
                'Nom Projet' => $project->name,
                'Description' => $project->description,
                'Date Début' => $project->start_date,
                'Date Fin' => $project->end_date,
                'Manager' => $project->manager->name,
                'Tâche' => '',
                'Description Tâche' => '',
                'Assigné à' => '',
                'Statut Tâche' => ''
            ];
            $data[] = $projectData;

            // Ajouter les tâches
            foreach ($project->tasks as $task) {
                $taskData = [
                    'ID Projet' => '',
                    'Nom Projet' => '',
                    'Description' => '',
                    'Date Début' => '',
                    'Date Fin' => '',
                    'Manager' => '',
                    'Tâche' => $task->name,
                    'Description Tâche' => $task->description,
                    'Assigné à' => $task->assignedUser->name,
                    'Statut Tâche' => $task->status
                ];
                $data[] = $taskData;
            }
        }

        // Générer le fichier Excel
        $filename = 'tous_les_projets_' . date('Y-m-d') . '.xlsx';
        return Excel::downloadFromCollection($data, $filename);
    }
}
