<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiController extends Controller
{
    public function chat(Request $request)
    {
        $message = $request->input('message');

        $response = Http::post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'), [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $message]
                    ]
                ] 
            ]
        ]);

        if ($response->failed()) {
            return response()->json(['reply' => 'Une erreur est survenue avec Gemini.'], 500);
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune réponse.';

        return response()->json(['reply' => $text]);
    }
}
