<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use PDF;

class PDFExportController extends Controller
{
    /**
     * Export un projet spécifique avec ses utilisateurs et tâches en PDF
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
            ->where('manager_id', Auth::id()) // S'assurer que le manager ne peut exporter que ses propres projets
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Projet non trouvé ou vous n\'êtes pas autorisé à y accéder'], 404);
        }

        // Récupérer tous les utilisateurs assignés à des tâches dans ce projet
        $userIds = $project->tasks->pluck('assigned_to')->unique()->filter();
        $users = User::whereIn('id', $userIds)->get();

        // Générer le PDF
        $pdf = PDF::loadView('pdf.project_export', [
            'project' => $project,
            'users' => $users,
            'tasks' => $project->tasks
        ]);

        // Définir le nom du fichier
        $filename = 'projet_' . $project->name . '_' . date('Y-m-d') . '.pdf';

        // Retourner le PDF pour téléchargement
        return $pdf->download($filename);
    } 

    /**
     * Export tous les projets du manager connecté en PDF
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

        // Générer le PDF
        $pdf = PDF::loadView('pdf.all_projects_export', [
            'projects' => $projects
        ]);

        // Définir le nom du fichier
        $filename = 'tous_les_projets_' . date('Y-m-d') . '.pdf';

        // Retourner le PDF pour téléchargement
        return $pdf->download($filename);
    }

    /**
     * Export la liste des projets avec leurs utilisateurs sans tâches en PDF
     *
     * @return \Illuminate\Http\Response
     */
    public function exportProjectsWithUsers()
    {
        // Vérifier que l'utilisateur est un manager
        if (!Auth::user()->isManager()) {
            return response()->json(['message' => 'Non autorisé. Seuls les managers peuvent exporter des projets.'], 403);
        }

        // Récupérer tous les projets du manager avec leurs utilisateurs
        $projects = Project::with(['users'])
            ->where('manager_id', Auth::id())
            ->get();

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'Aucun projet trouvé'], 404);
        }

        // Générer le PDF
        $pdf = PDF::loadView('pdf.projects_with_users_export', [
            'projects' => $projects
        ]);

        // Définir le nom du fichier
        $filename = 'liste_projets_employes_' . date('Y-m-d') . '.pdf';

        // Retourner le PDF pour téléchargement
        return $pdf->download($filename);
    }
}
