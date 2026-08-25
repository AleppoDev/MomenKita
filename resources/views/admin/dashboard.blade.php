@extends('admin.layout')

@section('title', 'Gambar')

@php
    $batches = (int) ceil(max($totalPhotos, 1) / $batchSize);
    $megabytes = $totalBytes > 0 ? round($totalBytes / 1048576, 1) : 0;
@endphp

@section('content')
    <div class="page-head">
        <h1>Gambar majlis</h1>
        <p>Semua yang tetamu kongsi. Muat turun dalam resolusi asal untuk simpanan pengantin.</p>
    </div>

    <div class="stats">
        <div class="stat">
            <p class="stat__label">Jumlah gambar</p>
            <p class="stat__value">{{ number_format($totalPhotos) }}</p>
        </div>
        <div class="stat">
            <p class="stat__label">Saiz simpanan</p>
            <p class="stat__value">{{ $megabytes >= 1024 ? round($megabytes / 1024, 2) . ' GB' : $megabytes . ' MB' }}</p>
        </div>
        <div class="stat">
            <p class="stat__label">Tetamu bernama</p>
            <p class="stat__value">{{ number_format($contributors) }}</p>
        </div>
        <div class="stat">
            <p class="stat__label">Disembunyikan</p>
            <p class="stat__value">{{ number_format($hiddenPhotos) }}</p>
        </div>
    </div>

    @unless ($cameraWillWork)
        <div class="notice notice--warn">
            <strong>Kamera tidak akan berfungsi untuk tetamu.</strong>
            Laman ini dihidangkan melalui HTTP, dan kamera pelayar hanya wujud atas HTTPS.
            Tetamu akan nampak halaman yang sempurna, tekan &ldquo;Buka kamera&rdquo;, dan tiada apa berlaku &mdash;
            tiada mesej ralat. Pasang sijil SSL sebelum hari majlis.
        </div>
    @endunless

    @unless ($zipAvailable)
        <div class="notice notice--warn">
            Muat turun ZIP tidak tersedia kerana sambungan PHP <code>zip</code> tidak aktif.
            Buka <code>extension=zip</code> dalam <code>php.ini</code> dan mulakan semula PHP.
            Muat turun satu-satu gambar tetap berfungsi.
        </div>
    @endunless

    <div class="toolbar">
        @if ($zipAvailable && $totalPhotos > 0)
            @for ($batch = 1; $batch <= $batches; $batch++)
                <form method="POST" action="{{ route('admin.archives.store') }}">
                    @csrf
                    <input type="hidden" name="batch" value="{{ $batch }}">
                    <button type="submit" class="btn">
                        @if ($batches === 1)
                            Sediakan ZIP semua gambar
                        @else
                            Sediakan ZIP kumpulan {{ $batch }} / {{ $batches }}
                        @endif
                    </button>
                </form>
            @endfor
        @endif

        <a class="btn btn--ghost" href="{{ route('qr') }}?size=1200" target="_blank" rel="noopener">Kod QR (PNG)</a>
        <a class="btn btn--ghost" href="{{ route('qr') }}?format=svg" target="_blank" rel="noopener">Kod QR (SVG untuk cetak)</a>
    </div>

    @if ($archives->isNotEmpty())
        <div class="card" style="margin-bottom:2rem">
            <h2 style="margin:0 0 0.3rem;font-size:1.05rem">Arkib ZIP</h2>
            <p style="margin:0 0 1rem;color:var(--ink-soft);font-size:0.85rem">
                Dibina di latar belakang. Muat semula halaman untuk lihat kemajuan.
            </p>

            <table style="width:100%;border-collapse:collapse;font-size:0.88rem">
                <tbody>
                @foreach ($archives as $archive)
                    <tr style="border-top:1px solid var(--line)">
                        <td style="padding:0.6rem 0.5rem 0.6rem 0">
                            Kumpulan {{ $archive->batch }}
                            <span style="color:var(--ink-faint)">· {{ $archive->created_at?->diffForHumans() }}</span>
                        </td>
                        <td style="padding:0.6rem 0.5rem;color:var(--ink-soft)">
                            @if ($archive->status === \App\Models\Archive::READY)
                                {{ $archive->photo_count }} gambar · {{ $archive->humanSize() }}
                            @elseif ($archive->status === \App\Models\Archive::FAILED)
                                <span style="color:var(--danger)">Gagal: {{ $archive->error }}</span>
                            @else
                                Sedang disediakan…
                            @endif
                        </td>
                        <td style="padding:0.6rem 0;text-align:right;white-space:nowrap">
                            @if ($archive->isReady())
                                <a class="btn btn--tiny" href="{{ route('admin.archives.download', $archive) }}">Muat turun</a>
                            @endif

                            <form method="POST" action="{{ route('admin.archives.destroy', $archive) }}" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn--danger btn--tiny">Buang</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($photos->isEmpty())
        <div class="empty">
            <p>Belum ada gambar. Kongsi kod QR kepada tetamu untuk bermula.</p>
        </div>
    @else
        <div class="shots">
            @foreach ($photos as $photo)
                <article class="shot" data-hidden="{{ $photo->is_hidden ? 'true' : 'false' }}">
                    <div class="shot__frame">
                        <img src="{{ $photo->thumbUrl() }}" alt="Momen oleh {{ $photo->displayName() }}" loading="lazy">
                        @if ($photo->is_hidden)
                            <span class="shot__flag">Tersembunyi</span>
                        @endif
                    </div>

                    <div class="shot__body">
                        <span class="shot__name">{{ $photo->displayName() }}</span>
                        <span class="shot__meta">
                            {{ $photo->created_at?->format('d M, g:ia') }} · {{ $photo->humanSize() }}
                            @if ($photo->width && $photo->height)
                                · {{ $photo->width }}×{{ $photo->height }}
                            @endif
                        </span>
                        @if ($photo->caption)
                            <span class="shot__caption">{{ $photo->caption }}</span>
                        @endif
                    </div>

                    <div class="shot__actions">
                        <a class="btn btn--ghost btn--tiny" href="{{ route('admin.photos.download', $photo) }}">Muat turun</a>

                        <form method="POST" action="{{ route('admin.photos.toggle', $photo) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn--ghost btn--tiny">
                                {{ $photo->is_hidden ? 'Papar' : 'Sembunyi' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}"
                              onsubmit="return confirm('Buang gambar ini terus? Tindakan ini tidak boleh dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger btn--tiny">Buang</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pagination">
            {{ $photos->links('vendor.pagination.simple') }}
        </div>
    @endif
@endsection
