{{--
    Corak songket sebagai <defs> yang dikongsi.

    Dua motif klasik:
      · pucuk rebung — segi tiga bersarang, lazimnya menghias birai kain
      · bunga tabur  — kuntum kecil bertaburan merata pada badan kain

    Diletak sekali di awal <body>; elemen lain merujuknya dengan fill="url(#...)".
--}}
<svg class="songket-defs" width="0" height="0" aria-hidden="true" focusable="false">
    <defs>
        <pattern id="songket-pucuk" width="26" height="24" patternUnits="userSpaceOnUse">
            <path d="M13 2 L23 21 L3 21 Z" fill="none" stroke="currentColor" stroke-width="0.7"/>
            <path d="M13 9 L18.5 20 L7.5 20 Z" fill="none" stroke="currentColor" stroke-width="0.6"/>
            <circle cx="13" cy="17.5" r="1.5" fill="currentColor"/>
        </pattern>

        <pattern id="songket-tabur" width="168" height="168" patternUnits="userSpaceOnUse">
            {{-- Kuntum empat mata, disusun berselang supaya tidak nampak bergrid. --}}
            <g fill="currentColor">
                <path d="M40 26 l2.4 6.8 6.8 2.4 -6.8 2.4 -2.4 6.8 -2.4 -6.8 -6.8 -2.4 6.8 -2.4z"/>
                <path d="M126 74 l2 5.6 5.6 2 -5.6 2 -2 5.6 -2 -5.6 -5.6 -2 5.6 -2z"/>
                <path d="M92 140 l2.4 6.8 6.8 2.4 -6.8 2.4 -2.4 6.8 -2.4 -6.8 -6.8 -2.4 6.8 -2.4z"/>
                <circle cx="140" cy="18" r="1.8"/>
                <circle cx="18" cy="112" r="1.8"/>
            </g>
        </pattern>
    </defs>
</svg>
