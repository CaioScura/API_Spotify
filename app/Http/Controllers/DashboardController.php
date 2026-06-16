<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('spotify_token')) {
            return redirect('/login');
        }

        return view('dashboard.index', [
            'user' => session('spotify_user', ['name' => 'Usuário', 'image' => null]),
        ]);
    }

    public function playMood(string $mood)
    {
        $token = session('spotify_token');
        if (!$token) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        if (!$this->moodFilters($mood)) {
            return response()->json(['error' => 'Mood inválido'], 400);
        }

        $tracks = $this->fetchLikedTracks($token);

        if (empty($tracks)) {
            return response()->json(['error' => 'Nenhuma música curtida encontrada na sua conta.'], 404);
        }

        // 1ª tentativa: audio features (pode estar deprecado para apps novos)
        $matching = $this->filterByAudioFeatures($tracks, $mood, $token);

        // 2ª tentativa: gênero dos artistas
        if (empty($matching)) {
            $matching = $this->filterByGenre($tracks, $mood, $token);
        }

        // 3ª tentativa: qualquer música curtida aleatória
        if (empty($matching)) {
            $matching = $tracks;
        }

        $track = $matching[array_rand($matching)];

        return response()->json([
            'track_id'    => $track['id'] ?? null,
            'name'        => $track['name'] ?? 'Desconhecido',
            'artist'      => implode(', ', array_column($track['artists'] ?? [], 'name')),
            'album'       => $track['album']['name'] ?? '',
            'image'       => $track['album']['images'][0]['url'] ?? null,
            'spotify_url' => $track['external_urls']['spotify'] ?? null,
        ]);
    }

    private function fetchLikedTracks(string $token): array
    {
        $tracks = [];
        $url = 'https://api.spotify.com/v1/me/tracks?limit=50';
        $pages = 0;

        while ($url && $pages < 4) {
            $resp = Http::withToken($token)->get($url)->json();
            if (!isset($resp['items'])) break;
            foreach ($resp['items'] as $item) {
                if (isset($item['track']) && $item['track']) {
                    $tracks[] = $item['track'];
                }
            }
            $url = $resp['next'] ?? null;
            $pages++;
        }

        return $tracks;
    }

    private function filterByAudioFeatures(array $tracks, string $mood, string $token): array
    {
        $filters  = $this->moodFilters($mood);
        $trackIds = array_column($tracks, 'id');
        $features = [];

        foreach (array_chunk($trackIds, 100) as $chunk) {
            $resp = Http::withToken($token)
                ->get('https://api.spotify.com/v1/audio-features', ['ids' => implode(',', $chunk)])
                ->json();
            foreach ($resp['audio_features'] ?? [] as $feat) {
                if ($feat && isset($feat['id'], $feat['valence'])) {
                    $features[$feat['id']] = $feat;
                }
            }
        }

        // endpoint deprecado para apps novos — menos de 10% retornou dados
        if (count($features) < count($trackIds) * 0.1) {
            return [];
        }

        $matching = [];
        foreach ($tracks as $track) {
            $feat = $features[$track['id']] ?? null;
            if ($feat && $this->matchesMood($feat, $filters)) {
                $matching[] = $track;
            }
        }

        return $matching;
    }

    private function filterByGenre(array $tracks, string $mood, string $token): array
    {
        $keywords = $this->moodGenres($mood);
        if (empty($keywords)) return [];

        // coleta IDs únicos dos artistas
        $artistIds = [];
        foreach ($tracks as $track) {
            foreach ($track['artists'] as $artist) {
                $artistIds[$artist['id']] = true;
            }
        }

        // busca artistas em lotes de 50 para obter gêneros
        $artistGenres = [];
        foreach (array_chunk(array_keys($artistIds), 50) as $chunk) {
            $resp = Http::withToken($token)
                ->get('https://api.spotify.com/v1/artists', ['ids' => implode(',', $chunk)])
                ->json();
            foreach ($resp['artists'] ?? [] as $artist) {
                if ($artist && isset($artist['id'])) {
                    $artistGenres[$artist['id']] = $artist['genres'] ?? [];
                }
            }
        }

        $matching = [];
        foreach ($tracks as $track) {
            if ($this->trackMatchesGenre($track, $artistGenres, $keywords)) {
                $matching[] = $track;
            }
        }

        return $matching;
    }

    private function trackMatchesGenre(array $track, array $artistGenres, array $keywords): bool
    {
        foreach ($track['artists'] as $artist) {
            foreach ($artistGenres[$artist['id']] ?? [] as $genre) {
                $genre = strtolower($genre);
                foreach ($keywords as $keyword) {
                    if (str_contains($genre, $keyword)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function moodFilters(string $mood): ?array
    {
        return match ($mood) {
            'happy'     => ['valence' => ['min' => 0.6], 'energy' => ['min' => 0.5]],
            'sad'       => ['valence' => ['max' => 0.35], 'energy' => ['max' => 0.45]],
            'focus'     => ['energy' => ['min' => 0.3, 'max' => 0.65], 'speechiness' => ['max' => 0.15]],
            'relaxed'   => ['energy' => ['max' => 0.5], 'valence' => ['min' => 0.4]],
            'night'     => ['energy' => ['max' => 0.4], 'valence' => ['max' => 0.5]],
            'loving'    => ['valence' => ['min' => 0.55], 'energy' => ['min' => 0.2, 'max' => 0.7]],
            default     => null,
        };
    }

    private function moodGenres(string $mood): array
    {
        return match ($mood) {
            'happy'     => ['pop', 'dance', 'funk', 'disco', 'happy', 'party', 'tropical', 'electro'],
            'sad'       => ['sad', 'acoustic', 'indie', 'emo', 'folk', 'singer-songwriter', 'blues'],
            'focus'     => ['ambient', 'classical', 'electronic', 'instrumental', 'lo-fi', 'post-rock'],
            'relaxed'   => ['chill', 'acoustic', 'lo-fi', 'bossa nova', 'jazz', 'soft', 'mellow'],
            'night'     => ['r&b', 'jazz', 'soul', 'trip-hop', 'urban', 'blues', 'synthwave'],
            'loving'  => ['romance', 'r&b', 'soul', 'love', 'jazz', 'acoustic', 'indie', 'bossa nova'],
            default     => [],
        };
    }

    private function matchesMood(array $features, array $filters): bool
    {
        foreach ($filters as $key => $range) {
            $value = $features[$key] ?? null;
            if ($value === null) return false;
            if (isset($range['min']) && $value < $range['min']) return false;
            if (isset($range['max']) && $value > $range['max']) return false;
        }
        return true;
    }
}
