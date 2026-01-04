<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',        // ID de l'utilisateur destinataire 
        'title',          // Titre de la notification
        'message',        // Message de la notification
        'type',           // Type de notification (ex: 'delete_account_request', 'task_assigned', etc.)
        'read',           // Si la notification a été lue (boolean)
        'data',           // Données supplémentaires en JSON (ex: ID de la demande de suppression)
        'action_url',     // URL d'action (si applicable)
    ]; 

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read' => 'boolean',
        'data' => 'array',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    } 
}
