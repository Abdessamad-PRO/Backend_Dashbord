<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\ResetCodeNotification;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Envoie un code de vérification pour réinitialiser le mot de passe
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Générer un code de vérification à 6 chiffres
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Stocker le code en base de données avec une durée de validité
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // Récupérer l'utilisateur
        $user = User::where('email', $request->email)->first();

        // Envoyer la notification avec le code
        $user->notify(new ResetCodeNotification($code));

        return response()->json([
            'message' => 'Code de vérification envoyé à votre adresse email'
        ]);
    }

    /**
     * Vérifie le code de réinitialisation
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $records = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->get();

        if (!$records->isNotEmpty()) {
            return response()->json([
                'message' => 'Code de vérification invalide ou expiré'
            ], 422);
        }

        $record = $records->first();

        if (!Hash::check($request->code, $record->token)) {
            return response()->json([
                'message' => 'Code de vérification invalide ou expiré'
            ], 422);
        }

        if (Carbon::parse($record->created_at)->lt(now()->subMinutes(15))) {
            return response()->json([
                'message' => 'Code de vérification expiré'
            ], 422); 
        }

        return response()->json([
            'message' => 'Code de vérification valide'
        ]);
    }

    /**
     * Réinitialise le mot de passe avec le code de vérification
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $records = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->get();

        if (!$records->isNotEmpty()) {
            return response()->json([
                'message' => 'Code de vérification invalide ou expiré'
            ], 422);
        }

        $record = $records->first(); 

        if (!Hash::check($request->code, $record->token)) {
            return response()->json([
                'message' => 'Code de vérification invalide ou expiré'
            ], 422);
        }

        if (Carbon::parse($record->created_at)->lt(now()->subMinutes(15))) {
            return response()->json([
                'message' => 'Code de vérification expiré'
            ], 422);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Supprimer le code de réinitialisation 
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }
}
