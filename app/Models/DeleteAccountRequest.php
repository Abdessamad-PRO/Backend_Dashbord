<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 

class DeleteAccountRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'reason', 
        'status', // 'pending', 'approved', 'rejected'
        'processed_by', // ID de l'admin qui a traité la demande
        'processed_at', // Date de traitement de la demande
        'rejection_reason', // Raison du rejet (si applicable)
    ]; 

    /** 
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the delete account request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed the request.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
