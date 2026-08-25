{{--
    Pemisah: sepasang garis rambut halus dengan sekuntum bunga melur di tengah.
    Menggantikan label huruf besar yang lazimnya diletak di atas setiap tajuk.
--}}
<div class="divider {{ $class ?? '' }}" aria-hidden="true">
    <span class="divider__line"></span>

    <svg class="divider__bloom" viewBox="-22 -22 44 44" fill="currentColor" focusable="false">
        <g class="divider__petals">
            <ellipse rx="5.6" ry="9.4" cy="-9"/>
            <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(72)"/>
            <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(144)"/>
            <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(216)"/>
            <ellipse rx="5.6" ry="9.4" cy="-9" transform="rotate(288)"/>
        </g>
        <circle r="3.2" class="divider__core"/>
    </svg>

    <span class="divider__line"></span>
</div>
