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
            'scope' => 'user-read-email user-read-private user-top-read user-library-read',
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

        // busca perfil do usuário
        $profileResponse = Http::withToken($data['access_token'])
            ->get('https://api.spotify.com/v1/me');

        if (!$profileResponse->successful()) {
            return response('Erro ao buscar perfil do Spotify: ' . $profileResponse->body(), 500);
        }

        $profile = $profileResponse->json();

        $image = null;
        if (!empty($profile['images']) && is_array($profile['images'])) {
            $image = $profile['images'][0]['url'] ?? null;
        }

        session([
            'spotify_token' => $data['access_token'],
            'spotify_user'  => [
                'name'  => $profile['display_name'] ?? ($profile['id'] ?? 'Usuário'),
                'image' => $image,
                'id'    => $profile['id'] ?? null,
            ],
        ]);

        return redirect('/dashboard');
    }

    public function logout()
    {
        session()->flush();
        return redirect('/');
    }
}
