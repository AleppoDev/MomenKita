/* ==========================================================================
   MomenKita — gerakan lanjutan (GSAP)

   Lapisan tambahan sepenuhnya. Halaman sudah lengkap dan bergerak tanpa fail
   ini; kalau GSAP gagal dimuatkan pada wifi dewan yang sesak, tiada apa yang
   rosak dan tiada kandungan yang hilang.
   ========================================================================== */

(function () {
  'use strict';

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reduced || !window.gsap || !window.ScrollTrigger || !window.SplitText) {
    return;
  }

  gsap.registerPlugin(ScrollTrigger, SplitText);

  /* gsap.from() menyembunyikan sasaran serta-merta dan hanya memulangkannya
     apabila tween benar-benar berjalan. Pada tab tersembunyi, perender
     headless, atau peranti yang tersekat, ia mungkin tidak pernah berjalan —
     dan halaman terbit tanpa nama mahupun gambar. Setiap kemasukan diletak
     pada satu garis masa yang dipaksa ke penghujungnya selepas tempoh ini. */
  var ENTRANCE_SAFETY_MS = 3500;

  var entrance = gsap.timeline({ delay: 0.3 });

  window.setTimeout(function () {
    if (entrance.progress() < 1) {
      entrance.progress(1);
    }
  }, ENTRANCE_SAFETY_MS);

  /* --- Nama pengantin, huruf demi huruf --------------------------------- */

  function revealNames() {
    var names = document.querySelectorAll('.hero__names > span:not(.hero__amp)');

    if (!names.length) {
      return;
    }

    Array.prototype.forEach.call(names, function (name, index) {
      var split = new SplitText(name, { type: 'chars' });

      entrance.from(split.chars, {
        opacity: 0,
        yPercent: 40,
        // Huruf tiba rapat-rapat; jarak lebih lebar membuatkan nama terasa
        // dieja satu-satu, bukan terbit sebagai satu nama.
        stagger: 0.028,
        duration: 0.9,
        ease: 'power3.out'
      }, index === 0 ? 0 : '-=0.55');
    });

    var amp = document.querySelector('.hero__amp');

    if (amp) {
      entrance.from(amp, { opacity: 0, scale: 0.6, duration: 0.7, ease: 'back.out(1.7)' }, '-=0.9');
    }
  }

  /* --- Gerbang hanyut perlahan semasa ditatal ---------------------------- */

  function parallaxPortrait() {
    var portrait = document.querySelector('.portrait');
    var hero = document.querySelector('.hero');

    if (!portrait || !hero) {
      return;
    }

    /* Gerbang tidak lagi membawa [data-enter]. Animasi CSS mengatasi gaya
       inline, dan dengan fill-mode `both` ia menahan transform selamanya —
       jadi dua sistem bertarung dan parallax tidak pernah bergerak. GSAP
       kini pemilik tunggal transform elemen ini, termasuk kemasukannya. */
    entrance.from(portrait, { opacity: 0, y: 24, duration: 1.1, ease: 'power3.out' }, 0);

    gsap.to(portrait, {
      yPercent: -9,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: 0.7
      }
    });

    // Gambar di dalam bergerak lebih perlahan daripada bingkainya, memberi
    // sedikit kedalaman tanpa menampakkan tepi bingkai terkopek.
    gsap.to(portrait.querySelector('.portrait__frame img'), {
      yPercent: 6,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: 0.7
      }
    });
  }

  /* --- Mula -------------------------------------------------------------- */

  function start() {
    // SplitText mengukur kedudukan huruf, jadi fon mesti siap dahulu — kalau
    // tidak, huruf dipecahkan pada metrik fon sandaran dan melompat.
    revealNames();
    parallaxPortrait();
    ScrollTrigger.refresh();
  }

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(start);
  } else {
    window.addEventListener('load', start);
  }
})();
