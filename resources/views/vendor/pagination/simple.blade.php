@if ($paginator->hasPages())
    <nav style="display:flex;gap:0.6rem;align-items:center" aria-label="Halaman gambar">
        @if ($paginator->onFirstPage())
            <span class="btn btn--ghost btn--tiny" aria-disabled="true" style="opacity:0.45">Sebelum</span>
        @else
            <a class="btn btn--ghost btn--tiny" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelum</a>
        @endif

        <span style="font-size:0.85rem;color:var(--ink-soft)">
            Halaman {{ $paginator->currentPage() }} daripada {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="btn btn--ghost btn--tiny" href="{{ $paginator->nextPageUrl() }}" rel="next">Seterusnya</a>
        @else
            <span class="btn btn--ghost btn--tiny" aria-disabled="true" style="opacity:0.45">Seterusnya</span>
        @endif
    </nav>
@endif
