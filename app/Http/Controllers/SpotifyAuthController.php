<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpotifyAuthController extends Controller
{
    //funcao redirect
    public function redirect()
    {
        $clientId = config('services.spotify.client_id');
        $redirectUri = config('services.spotify.redirect');

        if (!$clientId || !$redirectUri || $clientId === 'your_spotify_client_id_here') {
            return response('Spotify credentials not configuradas ou ainda usam o placeholder. Atualize SPOTIFY_CLIENT_ID e SPOTIFY_REDIRECT_URI no .env.', 500);
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => 'user-read-email user-top-read',
        ]);

        return redirect('https://accounts.spotify.com/authorize?' . $query);
    }


    //funcao callback
    public function callback(Request $request){
        if (!$request->has('code')) {
            return 'Erro ao autenticar com Spotify';
        }

        $clientId = config('services.spotify.client_id');
        $clientSecret = config('services.spotify.client_secret');
        $redirectUri = config('services.spotify.redirect');

        if (!$clientId || !$clientSecret || !$redirectUri || $clientId === 'your_spotify_client_id_here') {
            return response('Spotify credentials not configuradas ou ainda usam o placeholder. Atualize SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET e SPOTIFY_REDIRECT_URI no .env.', 500);
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ])
            ->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
                'redirect_uri' => $redirectUri,
            ]);

        if (!$response->successful()) {
            return response('Erro ao obter token Spotify: ' . $response->body(), 500);
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
            return response('Resposta inesperada do Spotify: ' . json_encode($data), 500);
        }

        // salva token na sessão
        session([
            'spotify_token' => $data['access_token']
        ]);

        return redirect('/');
    }
}
