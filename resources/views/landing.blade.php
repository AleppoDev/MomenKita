@php
    $bride = data_get($settings, 'bride_name', 'Pengantin Perempuan');
    $groom = data_get($settings, 'groom_name', 'Pengantin Lelaki');
    $date = data_get($settings, 'wedding_date', '');
    $venue = data_get($settings, 'venue_name', '');
    $address = data_get($settings, 'venue_address', '');
    $hashtag = data_get($settings, 'hashtag', '');
    $heroNote = data_get($settings, 'hero_note', '');
    $cameraNote = data_get($settings, 'camera_note', '');
    $title = $bride . ' & ' . $groom;

    $photoPath = data_get($settings, 'couple_photo');
    $couplePhoto = $photoPath ? Illuminate\Support\Facades\Storage::disk('public')->url($photoPath) : null;
@endphp
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#fbf8f3">
    <title>{{ $title }} — MomenKita</title>
    <meta name="description" content="Kongsi momen anda di majlis perkahwinan {{ $title }}.">

    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="Rakam dan kongsi momen anda di majlis kami.">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

@include('partials.songket')

<header class="hero">
    <svg class="weave" aria-hidden="true" focusable="false">
        <rect width="100%" height="100%" fill="url(#songket-tabur)"/>
    </svg>

    <div class="petals" data-petals aria-hidden="true"></div>

    <div class="hero__inner">
        <p class="hero__intro" data-enter="1">Dengan penuh kesyukuran, kami menjemput anda</p>

        @if ($couplePhoto)
            <figure class="portrait">
                <svg class="portrait__crown" viewBox="-22 -22 44 44" fill="currentColor" aria-hidden="true">
                    <g class="divider__petals">
                        <ellipse rx="5.6" ry="9.4" cy="-9"/>
                        <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(72)"/>
                        <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(144)"/>
                        <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(216)"/>
                        <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(288)"/>
                    </g>
                    <circle r="3.2" fill="var(--ivory)"/>
                </svg>

                <div class="portrait__frame">
                    <img src="{{ $couplePhoto }}" alt="{{ $bride }} dan {{ $groom }}">
                </div>

                @include('partials.sprig', ['class' => 'portrait__sprig portrait__sprig--left'])
                @include('partials.sprig', ['class' => 'portrait__sprig portrait__sprig--right'])
            </figure>
        @endif

        <h1 class="hero__names">
            <span style="display:block">{{ $bride }}</span>
            <span class="hero__amp">&amp;</span>
            <span style="display:block">{{ $groom }}</span>
        </h1>

        <div data-enter="5">
            @include('partials.divider')

            @if ($date)
                <p class="hero__meta"><strong>{{ $date }}</strong></p>
            @endif

            @if ($venue)
                <p class="hero__meta">{{ $venue }}</p>
            @endif

            @if ($heroNote)
                <p class="hero__note">{{ $heroNote }}</p>
            @endif
        </div>
    </div>

    <a class="hero__scroll" href="#rakam">Tatal ke bawah</a>
</header>

@if ($date || $venue || $hashtag)
    <section class="section">
        <div class="page">
            <div class="vows" data-reveal>
                @if ($date)
                    <div class="vows__item">
                        <span class="vows__label">Tarikh</span>
                        <p class="vows__value">{{ $date }}</p>
                    </div>
                @endif

                @if ($venue)
                    <div class="vows__item">
                        <span class="vows__label">Bertempat di</span>
                        <p class="vows__value">{{ $venue }}</p>
                        @if ($address)
                            <p class="vows__sub">{{ $address }}</p>
                        @endif
                    </div>
                @endif

                @if ($hashtag)
                    <div class="vows__item">
                        <span class="vows__label">Kongsikan dengan</span>
                        <p class="vows__value">{{ $hashtag }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

<section class="section section--tint" id="rakam">
    <div class="page">
        <div class="section__head" data-reveal>
            <h2 class="section__title">Ambil gambar, terus masuk galeri</h2>
            @if ($cameraNote)
                <p class="section__lead">{{ $cameraNote }}</p>
            @endif
            @include('partials.divider', ['class' => 'divider--section'])
        </div>

        <div class="capture" data-capture data-reveal data-reveal-delay="1">
            <div class="capture__stage" data-stage>
                <video class="capture__video" data-video playsinline muted hidden></video>
                <img class="capture__preview" data-preview alt="Gambar yang baru anda ambil" hidden>

                <div class="capture__placeholder" data-placeholder>
                    <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                        <path d="M3 8.5A2.5 2.5 0 0 1 5.5 6h1.7a1 1 0 0 0 .83-.44l.94-1.4A1 1 0 0 1 9.8 3.7h4.4a1 1 0 0 1 .83.45l.94 1.4A1 1 0 0 0 16.8 6h1.7A2.5 2.5 0 0 1 21 8.5v9A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z"/>
                        <circle cx="12" cy="12.8" r="3.6"/>
                    </svg>
                    <p>Tekan butang di bawah untuk buka kamera</p>
                </div>
            </div>

            <div class="capture__actions">
                <button type="button" class="btn" data-action="open">Buka kamera</button>
                <button type="button" class="btn btn--ghost" data-action="pick">Pilih dari galeri</button>
                <button type="button" class="btn" data-action="shoot" hidden>Ambil gambar</button>
                <button type="button" class="btn btn--ghost" data-action="retake" hidden>Ambil semula</button>
                <button type="button" class="btn" data-action="send" hidden>Hantar gambar</button>
            </div>

            <div class="capture__fields">
                <label class="field">
                    <span class="field__label">Nama anda, kalau sudi</span>
                    <input type="text" class="field__input" data-name maxlength="80" placeholder="Contoh: Kak Long" autocomplete="name">
                </label>

                <label class="field">
                    <span class="field__label">Doa atau ucapan</span>
                    <textarea class="field__textarea" data-caption maxlength="500" placeholder="Tulis sepatah dua untuk pengantin…"></textarea>
                </label>
            </div>

            <p class="capture__status" data-status role="status" aria-live="polite"></p>
            <div class="capture__bar" data-bar><span></span></div>

            <div class="queue" data-queue>
                <p class="queue__title">Dalam giliran</p>
                <div data-queue-list></div>
            </div>

            <p class="capture__hint">Gambar dikecilkan sedikit sebelum dihantar supaya laju walaupun talian sesak.</p>

            <input type="file" accept="image/*" data-file hidden>
            <input type="file" accept="image/*" capture="environment" data-native hidden>
        </div>
    </div>
</section>

<section class="section" data-gallery>
    <div class="page">
        <div class="section__head" data-reveal>
            <h2 class="section__title">Momen dari mata tetamu</h2>
        </div>

        <p class="gallery__count" data-count data-value="{{ $photoCount }}">
            {{ $photoCount === 0 ? 'Belum ada gambar lagi' : $photoCount . ' momen dikongsi setakat ini' }}
        </p>

        <div class="gallery" data-grid
             data-oldest="{{ $photos->last()?->id ?? 0 }}"
             data-newest="{{ $photos->first()?->id ?? 0 }}">
            @foreach ($photos as $photo)
                <figure class="shot"
                        data-id="{{ $photo->id }}"
                        data-full="{{ $photo->originalUrl() }}"
                        data-by="{{ $photo->guest_name }}">
                    <img src="{{ $photo->thumbUrl() }}"
                         alt="Momen dirakam oleh {{ $photo->displayName() }}"
                         loading="lazy"
                         @if ($photo->width && $photo->height)
                             style="aspect-ratio:{{ $photo->width }}/{{ $photo->height }}"
                         @endif>
                    @if ($photo->guest_name || $photo->caption)
                        <figcaption class="shot__caption">
                            @if ($photo->guest_name)
                                <div class="shot__name">{{ $photo->guest_name }}</div>
                            @endif
                            @if ($photo->caption)
                                <div class="shot__text">{{ $photo->caption }}</div>
                            @endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>

        <div class="gallery__empty" data-empty @if ($photoCount > 0) hidden @endif>
            <p>Galeri masih menunggu momen pertama. Jadilah orang yang memulakannya.</p>
        </div>

        <div class="gallery__more">
            <button type="button" class="btn btn--ghost" data-more @if ($photoCount <= 24) hidden @endif>Lihat lagi</button>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="page">
        <svg class="band band--ornament" aria-hidden="true" focusable="false">
            <rect width="100%" height="100%" fill="url(#songket-pucuk)"/>
        </svg>

        @include('partials.sprig', ['class' => 'sprig--footer'])

        @if ($hashtag)
            <p class="footer__hashtag">{{ $hashtag }}</p>
        @endif
        <p>{{ $title }}@if ($date) · {{ $date }} @endif</p>

        <p class="footer__credit">
            Direka &amp; dibangunkan oleh
            <a href="https://github.com/AleppoDev" target="_blank" rel="noopener">AleppoDev</a>
        </p>
    </div>
</footer>

<div class="lightbox" data-lightbox data-open="false">
    <button type="button" class="lightbox__close" data-lightbox-close aria-label="Tutup">&times;</button>
    <img alt="Momen dalam saiz penuh">
    <p class="lightbox__bar" data-lightbox-bar></p>
</div>

<script>
    window.MomenKita = {
        csrf: @json(csrf_token()),
        uploadUrl: @json(route('photos.store')),
        feedUrl: @json(route('photos.index')),
        sinceUrl: @json(route('photos.since'))
    };
</script>
<script src="{{ asset('js/momenkita.js') }}" defer></script>

{{-- Gerakan lanjutan. Dihoskan sendiri, bukan CDN: kalau internet dewan
     tersekat, halaman tetap hidup sepenuhnya tanpa fail-fail ini. --}}
<script src="{{ asset('js/vendor/gsap.min.js') }}" defer></script>
<script src="{{ asset('js/vendor/ScrollTrigger.min.js') }}" defer></script>
<script src="{{ asset('js/vendor/SplitText.min.js') }}" defer></script>
<script src="{{ asset('js/motion.js') }}" defer></script>

</body>
</html>
