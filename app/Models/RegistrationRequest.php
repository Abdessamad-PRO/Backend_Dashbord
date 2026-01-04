<?php
// app/Models/RegistrationRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prenom',
        'email',
        'role',
        'phone',
        'status', // 'pending', 'approved', 'rejected' 
        'rejection_reason',
        'token',
        'approval_token',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}