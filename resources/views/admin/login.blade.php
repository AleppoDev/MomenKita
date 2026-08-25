<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log masuk — MomenKita</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>

<div class="login">
    <div class="card login__card">
        <h1 class="login__title">Panel MomenKita</h1>
        <p class="login__sub">Masukkan kata laluan untuk menguruskan gambar majlis.</p>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="form">
            @csrf

            <div class="field">
                <label class="field__label" for="password">Kata laluan</label>
                <input type="password" id="password" name="password" class="field__input"
                       autocomplete="current-password" autofocus required>
                @error('password')
                    <span class="field__error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <button type="submit" class="btn">Log masuk</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
