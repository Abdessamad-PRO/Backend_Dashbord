<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PDFExportController;
use App\Http\Controllers\DeleteAccountRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ExcelExportController;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\TaskCancellationRequestController;
use App\Http\Controllers\TaskStatusChangeRequestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// }); 
Route::post('/chat', [GeminiController::class, 'chat']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); 

// Routes protégées
Route::middleware('auth:sanctum')->group(function () { 
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); 
    Route::put('/profile', [AuthController::class, 'updateProfile']);  
     
    // Routes pour les notifications (accessibles à tous les utilisateurs authentifiés) 
    Route::prefix('notifications')->group(function () { 
        Route::get('/', [NotificationController::class, 'getMyNotifications']);
        Route::get('/unread', [NotificationController::class, 'getMyUnreadNotifications']);
        Route::get('/count', [NotificationController::class, 'countMyUnreadNotifications']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']); 
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']); 
        Route::delete('/{id}', [NotificationController::class, 'deleteNotification']); 
        Route::delete('/', [NotificationController::class, 'deleteAllNotifications']); 
    }); 
    
    // Routes pour les demandes de suppression de compte (accessibles à tous les utilisateurs authentifiés)
    Route::post('/account/delete-request', [DeleteAccountRequestController::class, 'requestAccountDeletion']); 
    Route::get('/account/delete-request/status', [DeleteAccountRequestController::class, 'getMyRequestStatus']);
    Route::post('/task-cancellation-request/{taskId}', [TaskCancellationRequestController::class, 'store']);
    Route::post('/task-status-change-request/{taskId}', [TaskStatusChangeRequestController::class, 'requestStatusChange']);

    
    // Routes spécifiques aux rôles 
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Admin Dashboard']);
        }); 
        
        // Routes pour la gestion des demandes de suppression de compte (accessibles uniquement aux administrateurs)
        Route::prefix('admin/delete-requests')->group(function () { 
            Route::get('/', [DeleteAccountRequestController::class, 'getAllRequests']); 
            Route::get('/pending', [DeleteAccountRequestController::class, 'getPendingRequests']); 
            Route::get('/{id}', [DeleteAccountRequestController::class, 'getRequest']); 
            Route::post('/{id}/approve', [DeleteAccountRequestController::class, 'approveDeletion']); /////changement du nim de la methode 
            Route::post('/{id}/reject', [DeleteAccountRequestController::class, 'rejectDeletion']);  //////changement du nom de la methode
            
            Route::delete('/users/{id}', [AuthController::class, 'destroy']); 
        }); 
    }); 
    
    // Routes pour les managers uniquement
    Route::middleware('role:manager')->group(function () {
        Route::get('/manager/projects', function () {
            return response()->json(['message' => 'Manager Projects']);
        });
        
        // Routes pour la gestion des projets (accessibles uniquement aux managers)
        Route::post('/projects', [ProjectController::class, 'store']);  
        Route::put('/projects/{id}', [ProjectController::class, 'update']); 
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']); 
        Route::get('/assign-user', [ProjectController::class, 'getEmployees']); 
        
        // Routes pour la gestion des tâches (accessibles uniquement aux managers)
        Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store']);
        Route::put('/projects/{projectId}/tasks/{taskId}', [TaskController::class, 'update']);
        Route::delete('/projects/{projectId}/tasks/{taskId}', [TaskController::class, 'destroy']);
        Route::get('/assign-user', [TaskController::class, 'getEmployees']);
        Route::get('/manager/tasks', [TaskController::class, 'getTasksForManager']);

        // Routes pour l'exportation PDF (accessibles uniquement aux managers) 
        Route::get('/export/projects/{projectId}/pdf', [PDFExportController::class, 'exportProject']);
        Route::get('/export/projects/pdf', [PDFExportController::class, 'exportAllProjects']);
        Route::get('/export/projects-with-users', [PDFExportController::class, 'exportProjectsWithUsers']);
        
        // Routes d'export Excel
        Route::get('/export/excel/project/{projectId}', [ExcelExportController::class, 'exportProject']);
        Route::get('/export/excel/projects', [ExcelExportController::class, 'exportAllProjects']);

        // Routes pour la gestion des demandes d'annulation de tâche (accessibles uniquement aux managers)
        // Route::get('/manager/task-cancellations', [TaskCancellationRequestController::class, 'index']);
        Route::post('/manager/task-cancellations/{id}/reject', [TaskCancellationRequestController::class, 'reject']);
        Route::post('/manager/task-cancellations/{id}/approve', [TaskCancellationRequestController::class, 'approve']);

        // Approuver une demande (manager)
        Route::post('/task-status-change-request/{id}/approve', [TaskStatusChangeRequestController::class, 'approve']);

        // Rejeter une demande (manager)
        Route::post('/task-status-change-request/{id}/reject', [TaskStatusChangeRequestController::class, 'reject']);
    }); 
     
    // Routes pour les projets et tâches (accessibles à tous les utilisateurs authentifiés)
    // Les contrôleurs gèrent les permissions pour n'afficher que ce que l'utilisateur a le droit de voir
    Route::get('/projects', [ProjectController::class, 'index']); 
    Route::get('/projects/{id}', [ProjectController::class, 'show']); 
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index']);
    Route::get('/projects/{projectId}/tasks/{taskId}', [TaskController::class, 'show']); 
    Route::get('/employee/tasks', [TaskController::class, 'getTasksForEmployee']);
    Route::get('/employees/stats', [TaskController::class, 'getEmployeesWithStats']);
    Route::get('/managers/stats', [TaskController::class, 'getManagersStatsFromTasks']);
}); 

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']); 


// Routes publiques
Route::post('/request-registration', [RegistrationController::class, 'requestRegistration']);
Route::post('/set-initial-password', [RegistrationController::class, 'setInitialPassword']);

// Routes pour les actions directes depuis les emails (sans authentification)
Route::get('/admin/approve-registration-direct/{id}/{token}', [RegistrationController::class, 'approveRegistrationDirect']);
Route::get('/admin/reject-registration-form/{id}/{token}', [RegistrationController::class, 'showRejectForm']);
Route::post('/admin/reject-registration-direct/{id}/{token}', [RegistrationController::class, 'rejectRegistrationDirect']);

// Routes pour la page de réinitialisation de mot de passe
Route::get('/set-password/{token}', [RegistrationController::class, 'showSetPasswordForm']);

// Routes protégées (nécessitent authentification)
Route::middleware('auth:sanctum')->group(function () {
    // Routes pour l'administrateur
    Route::prefix('admin')->group(function () {
        Route::get('/pending-registrations', [RegistrationController::class, 'getPendingRegistrations']);
        Route::get('/registration/{id}', [RegistrationController::class, 'getRegistrationDetails']);
        Route::post('/approve-registration/{id}', [RegistrationController::class, 'approveRegistration']);
        Route::post('/reject-registration/{id}', [RegistrationController::class, 'rejectRegistration']);
    });
}); 
