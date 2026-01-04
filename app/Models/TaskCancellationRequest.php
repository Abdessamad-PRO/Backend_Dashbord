<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCancellationRequest extends Model
{ 
    use HasFactory;
    protected $fillable = [
        'task_id',
        'user_id',
        'name', 
        'reason',
        'status',
        'processed_at',
        'processed_by',
        'rejection_reason',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // 🔁 Relation avec la tâche
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // 🔁 Relation avec l'utilisateur (employé)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔁 Relation avec le manager qui a traité la demande
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
