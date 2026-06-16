<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Player Spotify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" href="{{ asset('assets/images/spotify_dark.svg') }}" type="image/svg+xml">
</head>
<body>

    <!-- ── Navbar ── -->
    <nav class="sp-navbar d-flex align-items-center justify-content-between">
        <a href="/dashboard" class="sp-brand">
            <img src="/assets/images/logo_spotify.png" alt="Logo" style="width: 40px; height: 40px;" class="logo me-2">
            {{-- <i class="bi bi-music-note-beamed me-1"></i>Mood Player --}}
            Mood Player Caiote
        </a>

        <div class="d-flex align-items-center gap-3">
            @if(!empty($user['image']))
                <img src="{{ $user['image'] }}" alt="Avatar" class="profile-avatar">
            @else
                <div class="profile-initial">
                    {{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}
                </div>
            @endif

            <span class="profile-name d-none d-sm-inline">{{ $user['name'] ?? 'Usuário' }}</span>

            <a href="/logout" class="btn-logout">
                <i class="bi bi-box-arrow-right me-1"></i>Sair
            </a>
        </div>
    </nav>

    <!-- ── Main ── -->
    <div class="main-content">
        <div>
            <h1 class="section-title">Como você está se sentindo?</h1>
            <div class="section-sub">Selecione um mood e tocaremos uma música das suas curtidas que combina.</div>
        </div>

        <!-- Mood grid -->
        <div class="row g-3">
            @php
                $moods = [
                    ['slug' => 'happy',      'emoji' => '😊', 'name' => 'Animado'],
                    ['slug' => 'sad',        'emoji' => '😢', 'name' => 'Triste demais'],
                    ['slug' => 'focus',      'emoji' => '🎯', 'name' => 'Focado'],
                    ['slug' => 'relaxed',    'emoji' => '😌', 'name' => 'Sussa'],
                    ['slug' => 'night',      'emoji' => '🌙', 'name' => 'Noturno'],
                    ['slug' => 'loving',  'emoji' => '❤️', 'name' => 'Apaixonadinho'],
                ];
            @endphp

            @foreach($moods as $mood)
            <div class="col-6 col-md-4 col-lg-4">
                <div class="mood-card mood-{{ $mood['slug'] }}"
                     onclick="selectMood(this, '{{ $mood['slug'] }}')">
                    <span class="mood-emoji">{{ $mood['emoji'] }}</span>
                    <div class="mood-name">{{ $mood['name'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Now Playing -->
        <div id="now-playing">
            {{-- <div class="now-playing-label"><i class="bi bi-vinyl me-1"></i>Tocando agora</div> --}}

            <div id="loading-track">
                <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                Buscando uma música para você...
            </div>

            <div id="error-track"></div>

            <div id="track-info">
                <iframe id="spotify-embed"
                    src=""
                    width="100%"
                    height="152"
                    frameborder="0"
                    allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                    loading="lazy"
                    style="border-radius: 12px;">
                </iframe>
                <div class="mt-2 text-end">
                    <a id="btn-open-spotify" href="#" target="_blank" rel="noopener" class="btn-sp">
                        <i class="bi bi-spotify"></i> Abrir no Spotify
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let activeCard = null;

        function selectMood(card, mood) {
            if (activeCard && activeCard !== card) {
                activeCard.classList.remove('active');
            }
            card.classList.add('active', 'loading');
            activeCard = card;

            // reset iframe para parar música anterior
            const embed = document.getElementById('spotify-embed');
            embed.src = '';

            document.getElementById('now-playing').style.display = 'block';
            document.getElementById('loading-track').style.display = 'block';
            document.getElementById('track-info').style.display = 'none';
            document.getElementById('error-track').style.display = 'none';

            fetch(`/mood/${mood}/play`)
                .then(r => r.json())
                .then(data => {
                    card.classList.remove('loading');
                    document.getElementById('loading-track').style.display = 'none';

                    if (data.error) {
                        showError(data.error);
                        return;
                    }

                    document.getElementById('track-info').style.display = 'block';

                    embed.src = `https://open.spotify.com/embed/track/${data.track_id}?utm_source=generator&theme=0`;
                    document.getElementById('btn-open-spotify').href = data.spotify_url ?? '#';
                })
                .catch(() => {
                    card.classList.remove('loading');
                    document.getElementById('loading-track').style.display = 'none';
                    showError('Erro de conexão. Tente novamente.');
                });
        }

        function showError(msg) {
            const el = document.getElementById('error-track');
            el.style.display = 'block';
            el.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${msg}`;
        }
    </script>
</body>
</html>
