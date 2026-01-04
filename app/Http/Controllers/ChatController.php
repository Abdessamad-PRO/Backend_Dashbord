<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function send(Request $request)
    { 
        $message = $request->input('message');

        $response = Http::withHeaders([ 
            'Authorization' => 'Bearer ' . env('HUGGING_FACE_TOKEN'),
            'Content-Type' => 'application/json', 
        ])->post('https://api-inference.https://huggingface.co/deepseek-ai/DeepSeek-R1-0528', [
            'inputs' => $message,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'reply' => 'Désolé, une erreur est survenue.',
            ]);
        }

        return response()->json([
            'reply' => $response->json()['generated_text'] ?? 'Aucune réponse générée.'
        ]);
    }
}
