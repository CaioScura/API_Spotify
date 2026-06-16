<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Player Caiote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" href="{{ asset('assets/images/spotify_dark.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="d-flex flex-column align-items-center justify-content-center"
         style="min-height: 100vh; gap: 2rem;">

        <a href="#" class="sp-brand d-flex align-items-center gap-2" style="font-size: 2.5rem; text-decoration: none;">
            <img src="/assets/images/logo_spotify.png" alt="Logo" style="width: 52px; height: 52px;">
            Mood Player Caiote
        </a>

        <a href="/login" class="btn-entrar-spotify">
            Entrar com Spotify
            <i class="bi bi-spotify ms-2"></i>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
