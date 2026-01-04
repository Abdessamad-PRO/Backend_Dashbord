<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;



class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:user,manager,admin'
        ]);

        $user = User::create([ 
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user', 
        ]);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur.']);
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

   public function login(Request $request): JsonResponse 
{ 
    // Validation : email et mot de passe uniquement
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]); 

    // Recherche utilisateur uniquement par email
    $user = User::where('email', $request->email)->first();

    // Vérification de l'existence et du mot de passe
    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Les informations d\'identification fournies sont incorrectes.'],
        ]);
    } 

    // Création du token d'authentification
    $token = $user->createToken('auth_token')->plainTextToken; 

    // Réponse JSON avec token et infos utilisateur
    return response()->json([
        'token' => $token,
        // 'user' => [
        //     'id' => $user->id,
        //     'name' => $user->name,
        //     'email' => $user->email,
        //     'role' => $user->role, 
        // ]
        'user' => $user->makeHidden(['password', 'remember_token']),
    ]);
}

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    } 

    public function user(Request $request): JsonResponse
    { 
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }  

    public function updateProfile(Request $request): JsonResponse
{
    try {
        $user = $request->user();

        // Validation des données
        $validated = $request->validate([
            'name' => 'nullable|string|max:255', 
            'prenom' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$user->id,
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'departement' => 'nullable|string|max:100',
            'bio' => 'nullable|string',
            'photo_de_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
        ]); 
        
        // Séparer les données texte de l'image
        $dataToUpdate = collect($validated)->except(['photo_de_profile'])->toArray();
        
        // Mettre à jour les données texte
        $user->update($dataToUpdate);

        // Gérer l'upload d'image si présent 
        if ($request->hasFile('photo_de_profile')) {
            // Supprimer l'ancienne image si elle existe 
            if ($user->photo_de_profile) { 
                $oldImagePath = public_path(str_replace('/storage', 'storage/app/public', $user->photo_de_profile));
                if (file_exists($oldImagePath)) { 
                    unlink($oldImagePath);
                } 
            }   
             // Stocker la nouvelle image 
            $path = $request->file('photo_de_profile')->store('profile_images', 'public');
            $user->photo_de_profile = '/storage/' . $path;
            // $user->photo_de_profile = '/storage/' . $path;  

            $user->save(); 
        }

        // Recharger le modèle pour avoir les dernières données
        $user->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès', 
            'user' => $user->makeHidden(['password', 'remember_token']) // Cacher les champs sensibles
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreurs de validation',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Erreur lors de la mise à jour du profil: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Une erreur s\'est produite lors de la mise à jour du profil'
        ], 500);
    }

}
public function destroy($id)
{

    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'message' => 'Utilisateur non trouvé.'
        ], 404);
    } 
    $user->delete();

    return response()->json([
        'message' => 'Utilisateur supprimé avec succès.'
    ], 200);

  }
}