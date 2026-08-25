<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — MomenKita</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>

<nav class="topbar">
    <div class="wrap topbar__inner">
        <a class="topbar__brand" href="{{ route('admin.dashboard') }}">MomenKita</a>

        <div class="topbar__nav">
            <a class="topbar__link" href="{{ route('admin.dashboard') }}"
               @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>Gambar</a>

            <a class="topbar__link" href="{{ route('admin.settings') }}"
               @if (request()->routeIs('admin.settings')) aria-current="page" @endif>Tetapan</a>

            <a class="topbar__link" href="{{ route('landing') }}" target="_blank" rel="noopener">Lihat laman</a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn--ghost btn--tiny">Log keluar</button>
            </form>
        </div>
    </div>
</nav>

<main class="wrap">
    @if (session('status'))
        <div class="notice" style="margin-top:1.5rem">{{ session('status') }}</div>
    @endif

    @yield('content')
</main>

</body>
</html>
