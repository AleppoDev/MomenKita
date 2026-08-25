@php use Illuminate\Support\Facades\Storage; @endphp
@extends('admin.layout')

@section('title', 'Tetapan')

@php
    $fields = [
        ['bride_name', 'Nama pengantin perempuan', 'text', null],
        ['groom_name', 'Nama pengantin lelaki', 'text', null],
        ['couple_slug', 'Nama fail muat turun', 'text', 'Digunakan sebagai nama fail ZIP, contoh: aisyah-firdaus'],
        ['wedding_date', 'Tarikh majlis', 'text', 'Ditulis bebas, contoh: 12 Disember 2026'],
        ['venue_name', 'Nama tempat', 'text', null],
        ['venue_address', 'Alamat', 'text', null],
        ['hashtag', 'Hashtag', 'text', null],
        ['hero_note', 'Ucapan di halaman utama', 'textarea', 'Muncul di bawah nama pengantin'],
        ['camera_note', 'Ayat di ruangan kamera', 'textarea', 'Menerangkan kepada tetamu apa yang perlu dibuat'],
    ];
@endphp

@section('content')
    <div class="page-head">
        <h1>Tetapan majlis</h1>
        <p>Butiran ini muncul di halaman yang tetamu lihat selepas mengimbas kod QR.</p>
    </div>

    <div class="card" style="margin-bottom:2rem">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @foreach ($fields as [$key, $label, $type, $hint])
                <div class="field">
                    <label class="field__label" for="{{ $key }}">{{ $label }}</label>

                    @if ($type === 'textarea')
                        <textarea id="{{ $key }}" name="{{ $key }}" class="field__textarea"
                                  maxlength="255">{{ old($key, data_get($settings, $key)) }}</textarea>
                    @else
                        <input type="text" id="{{ $key }}" name="{{ $key }}" class="field__input"
                               value="{{ old($key, data_get($settings, $key)) }}" maxlength="255">
                    @endif

                    @if ($hint)
                        <span class="field__hint">{{ $hint }}</span>
                    @endif

                    @error($key)
                        <span class="field__error">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach

            <div class="field">
                <label class="field__label" for="couple_photo">Gambar pengantin</label>

                @if (data_get($settings, 'couple_photo'))
                    <img src="{{ Storage::disk('public')->url(data_get($settings, 'couple_photo')) }}"
                         alt="Gambar pengantin semasa"
                         style="width:120px;border-radius:8px;border:1px solid var(--line);margin-bottom:0.5rem">
                @endif

                <input type="file" id="couple_photo" name="couple_photo" accept="image/*" class="field__input">
                <span class="field__hint">
                    Muncul dalam gerbang di halaman utama. Gambar menegak paling sesuai. Had 8 MB.
                    Biarkan kosong untuk kekalkan gambar sedia ada.
                </span>

                @error('couple_photo')
                    <span class="field__error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <button type="submit" class="btn">Simpan tetapan</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin:0 0 0.4rem;font-size:1.1rem">Kod QR untuk tetamu</h2>
        <p style="margin:0 0 1.2rem;color:var(--ink-soft);font-size:0.88rem">
            Imbasan membawa terus ke halaman majlis. Cetak versi SVG untuk kad meja supaya kekal tajam.
        </p>

        <div class="qr">
            <img src="{{ route('qr') }}?size=600" alt="Kod QR ke halaman majlis">
            <code style="font-size:0.82rem;color:var(--ink-soft)">{{ route('landing') }}</code>
            <div style="display:flex;gap:0.6rem;flex-wrap:wrap">
                <a class="btn btn--ghost btn--tiny" href="{{ route('qr') }}?size=1200" download="momenkita-qr.png">Muat turun PNG</a>
                <a class="btn btn--ghost btn--tiny" href="{{ route('qr') }}?format=svg" download="momenkita-qr.svg">Muat turun SVG</a>
            </div>
        </div>
    </div>
@endsection
