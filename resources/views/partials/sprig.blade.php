{{--
    Ranting bunga melur, dilukis tangan sebagai SVG.

    Setiap garisan membawa pathLength="1" supaya animasi "melukis sendiri"
    boleh ditulis sebagai dashoffset 1 → 0 tanpa perlu tahu panjang sebenar.
    Kelas diberi dari luar untuk menentukan saiz, putaran dan cerminan.
--}}
<svg class="sprig {{ $class ?? '' }}" viewBox="0 0 160 210" fill="none" aria-hidden="true" focusable="false">
    <g class="sprig__stems" stroke="currentColor" stroke-width="1.35" stroke-linecap="round">
        <path pathLength="1" class="sprig__stem" d="M14 206C29 168 21 126 46 96 66 72 88 58 108 32"/>
        <path pathLength="1" class="sprig__stem sprig__stem--b" d="M42 104C58 100 73 87 81 72"/>
        <path pathLength="1" class="sprig__stem sprig__stem--c" d="M28 148C43 146 58 137 67 124"/>
    </g>

    <g class="sprig__leaves" fill="currentColor">
        <path class="sprig__leaf" d="M0 0C7-8 22-10 31-3 22 6 7 5 0 0Z" transform="translate(46 96) rotate(-52)"/>
        <path class="sprig__leaf" d="M0 0C7-8 22-10 31-3 22 6 7 5 0 0Z" transform="translate(33 138) rotate(-14) scale(-1 1)"/>
        <path class="sprig__leaf" d="M0 0C6-7 18-8 26-2 18 5 6 4 0 0Z" transform="translate(63 71) rotate(-64)"/>
        <path class="sprig__leaf" d="M0 0C6-7 18-8 26-2 18 5 6 4 0 0Z" transform="translate(24 176) rotate(20) scale(-1 1)"/>
    </g>

    {{-- Kelopak bunga melur: lima kelopak bulat mengelilingi teras. --}}
    <g class="sprig__blooms" fill="currentColor">
        <g class="sprig__bloom" transform="translate(108 32)">
            <g class="sprig__petals">
                <ellipse rx="5.4" ry="8.8" cy="-8.4"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(72)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(144)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(216)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(288)"/>
            </g>
            <circle r="3.1" class="sprig__core"/>
        </g>

        <g class="sprig__bloom sprig__bloom--b" transform="translate(81 72) scale(0.78)">
            <g class="sprig__petals">
                <ellipse rx="5.4" ry="8.8" cy="-8.4"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(72)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(144)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(216)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(288)"/>
            </g>
            <circle r="3.1" class="sprig__core"/>
        </g>

        <g class="sprig__bloom sprig__bloom--c" transform="translate(67 124) scale(0.6)">
            <g class="sprig__petals">
                <ellipse rx="5.4" ry="8.8" cy="-8.4"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(72)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(144)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(216)"/>
                <ellipse rx="5.4" ry="8.8" cy="-8.4" transform="rotate(288)"/>
            </g>
            <circle r="3.1" class="sprig__core"/>
        </g>
    </g>
</svg>
