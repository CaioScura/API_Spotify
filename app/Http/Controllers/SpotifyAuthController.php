<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpotifyAuthController extends Controller
{
    //funcao redirect
    public function redirect()
    {
        $query = http_build_query([
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
            'scope' => 'user-read-email user-top-read',
        ]);

        return redirect('https://accounts.spotify.com/authorize?' . $query);
    }


    //funcao callback
    public function callback(Request $request){
        if (!$request->has('code')) {
            return 'Erro ao autenticar com Spotify';
        }

        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $request->code,
            'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
            'client_id' => env('SPOTIFY_CLIENT_ID'),
            'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        ]);

        $data = $response->json();

        // salva token na sessão
        session([
            'spotify_token' => $data['access_token']
        ]);

        return redirect('/');
    }
}
