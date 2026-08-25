/* ==========================================================================
   MomenKita — skrip tetamu
   Tiada rangka kerja, tiada langkah build. Semua yang tetamu perlukan pada
   hari majlis: buka kamera, ambil gambar, hantar walaupun talian tersekat.
   ========================================================================== */

(function () {
  'use strict';

  var config = window.MomenKita || {};

  /* Gambar dikecilkan sebelum dihantar. 2560px masih cukup tajam untuk cetakan
     A4, tetapi memotong saiz fail sekitar tiga suku — perbezaan antara berjaya
     dan gagal pada talian dewan yang sesak. */
  var MAX_EDGE = 2560;
  var JPEG_QUALITY = 0.9;
  var MAX_RETRIES = 3;
  var NAME_STORAGE_KEY = 'momenkita.nama';

  /* ---------------------------------------------------------------- utiliti */

  function el(selector, root) {
    return (root || document).querySelector(selector);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function readStoredName() {
    try {
      return window.localStorage.getItem(NAME_STORAGE_KEY) || '';
    } catch (e) {
      return '';
    }
  }

  function storeName(value) {
    try {
      if (value) {
        window.localStorage.setItem(NAME_STORAGE_KEY, value);
      }
    } catch (e) {
      /* Mod peribadi Safari menolak localStorage; abaikan sahaja. */
    }
  }

  /* ------------------------------------------------------------- motion */

  function prefersReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function revealAll() {
    var targets = document.querySelectorAll('[data-reveal]');

    Array.prototype.forEach.call(targets, function (node) {
      node.classList.add('is-visible');
    });
  }

  /**
   * Kandungan kelihatan sepenuhnya tanpa JavaScript. Kelas `js-motion` hanya
   * ditambah apabila pelayar benar-benar boleh memerhati tatalan, jadi tiada
   * seksyen yang tersembunyi kekal pada pelayar lama atau perender headless.
   */
  function setupMotion() {
    if (!('IntersectionObserver' in window)) {
      return;
    }

    document.documentElement.classList.add('js-motion');

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });

    Array.prototype.forEach.call(document.querySelectorAll('[data-reveal]'), function (node) {
      observer.observe(node);
    });

    // Jaring keselamatan: kalau pemerhati tidak pernah menyala kerana tab
    // tersembunyi atau tangkapan skrin automatik, dedahkan semuanya.
    window.setTimeout(revealAll, 2500);
  }

  /** Kelopak bunga melur yang hanyut perlahan di belakang tajuk. */
  function setupPetals() {
    var field = el('[data-petals]');

    if (!field || prefersReducedMotion()) {
      return;
    }

    var count = window.innerWidth < 700 ? 9 : 16;
    var tints = [
      'rgba(168, 130, 63, 0.45)',
      'rgba(194, 169, 120, 0.5)',
      'rgba(90, 107, 87, 0.26)',
      'rgba(255, 255, 255, 0.62)'
    ];

    var fragment = document.createDocumentFragment();

    for (var i = 0; i < count; i++) {
      var petal = document.createElement('span');
      petal.className = 'petal';

      var style = petal.style;
      style.setProperty('--petal-x', (Math.random() * 100).toFixed(2) + '%');
      style.setProperty('--petal-size', (7 + Math.random() * 9).toFixed(1) + 'px');
      style.setProperty('--petal-duration', (12 + Math.random() * 12).toFixed(1) + 's');
      // Lengah negatif supaya kelopak sudah berada di pertengahan turun
      // sebaik halaman dibuka, bukan semuanya bermula serentak dari atas.
      style.setProperty('--petal-delay', (-Math.random() * 20).toFixed(1) + 's');
      style.setProperty('--petal-drift', (Math.random() * 160 - 80).toFixed(0) + 'px');
      style.setProperty('--petal-spin', (180 + Math.random() * 420).toFixed(0) + 'deg');
      style.setProperty('--petal-opacity', (0.3 + Math.random() * 0.4).toFixed(2));
      style.setProperty('--petal-tint', tints[i % tints.length]);

      fragment.appendChild(petal);
    }

    field.appendChild(fragment);
  }

  /* ------------------------------------------------------- mampatan gambar */

  /**
   * Lukis fail ke kanvas pada saiz terhad dan pulangkan JPEG.
   * Kanvas juga membuang metadata EXIF, jadi orientasi mesti dibetulkan dahulu
   * oleh createImageBitmap yang menghormati EXIF pada pelayar moden.
   */
  function compress(file) {
    return new Promise(function (resolve) {
      var finish = function (blob) {
        resolve(blob && blob.size < file.size ? blob : file);
      };

      var draw = function (source, width, height) {
        var scale = Math.min(1, MAX_EDGE / Math.max(width, height));
        var canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(width * scale));
        canvas.height = Math.max(1, Math.round(height * scale));

        var ctx = canvas.getContext('2d');
        ctx.drawImage(source, 0, 0, canvas.width, canvas.height);

        if (canvas.toBlob) {
          canvas.toBlob(finish, 'image/jpeg', JPEG_QUALITY);
        } else {
          resolve(file);
        }
      };

      if (window.createImageBitmap) {
        window.createImageBitmap(file, { imageOrientation: 'from-image' })
          .then(function (bitmap) {
            draw(bitmap, bitmap.width, bitmap.height);
            bitmap.close();
          })
          .catch(function () { resolve(file); });
        return;
      }

      var url = URL.createObjectURL(file);
      var image = new Image();

      image.onload = function () {
        draw(image, image.naturalWidth, image.naturalHeight);
        URL.revokeObjectURL(url);
      };

      image.onerror = function () {
        URL.revokeObjectURL(url);
        resolve(file);
      };

      image.src = url;
    });
  }

  /* ------------------------------------------------------------ muat naik */

  function CaptureSpot(root, gallery) {
    this.root = root;
    this.gallery = gallery;

    this.stage = el('[data-stage]', root);
    this.video = el('[data-video]', root);
    this.preview = el('[data-preview]', root);
    this.placeholder = el('[data-placeholder]', root);
    this.statusNode = el('[data-status]', root);
    this.bar = el('[data-bar]', root);
    this.barFill = el('[data-bar] span', root);
    this.queueNode = el('[data-queue]', root);
    this.queueList = el('[data-queue-list]', root);

    this.nameInput = el('[data-name]', root);
    this.captionInput = el('[data-caption]', root);

    this.openBtn = el('[data-action="open"]', root);
    this.shootBtn = el('[data-action="shoot"]', root);
    this.retakeBtn = el('[data-action="retake"]', root);
    this.sendBtn = el('[data-action="send"]', root);
    this.pickBtn = el('[data-action="pick"]', root);
    this.fileInput = el('[data-file]', root);
    this.nativeInput = el('[data-native]', root);

    this.stream = null;
    this.pending = null;
    this.queue = [];
    this.sending = false;

    this.bind();

    var saved = readStoredName();
    if (saved && this.nameInput) {
      this.nameInput.value = saved;
    }
  }

  CaptureSpot.prototype.bind = function () {
    var self = this;

    this.openBtn.addEventListener('click', function () { self.openCamera(); });
    this.shootBtn.addEventListener('click', function () { self.shoot(); });
    this.retakeBtn.addEventListener('click', function () { self.reset(); });
    this.sendBtn.addEventListener('click', function () { self.send(); });

    this.pickBtn.addEventListener('click', function () { self.fileInput.click(); });

    this.fileInput.addEventListener('change', function (event) {
      if (event.target.files && event.target.files[0]) {
        self.acceptFile(event.target.files[0]);
      }
      event.target.value = '';
    });

    this.nativeInput.addEventListener('change', function (event) {
      if (event.target.files && event.target.files[0]) {
        self.acceptFile(event.target.files[0]);
      }
      event.target.value = '';
    });

    window.addEventListener('beforeunload', function (event) {
      if (self.queue.length || self.sending) {
        event.preventDefault();
        event.returnValue = '';
      }
    });

    // Matikan kamera bila tetamu tukar tab supaya bateri tidak habis.
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        self.stopStream();
      }
    });
  };

  CaptureSpot.prototype.status = function (message, tone) {
    this.statusNode.textContent = message || '';
    this.statusNode.setAttribute('data-tone', tone || '');
  };

  CaptureSpot.prototype.setButtons = function (state) {
    this.openBtn.hidden = state !== 'idle';
    this.pickBtn.hidden = state !== 'idle';
    this.shootBtn.hidden = state !== 'live';
    this.retakeBtn.hidden = state !== 'ready';
    this.sendBtn.hidden = state !== 'ready';
  };

  /** getUserMedia hanya wujud atas HTTPS (atau localhost). Kalau tiada, kita
   *  serahkan kepada aplikasi kamera telefon melalui input fail. */
  CaptureSpot.prototype.openCamera = function () {
    var self = this;
    var supported = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;

    if (!supported) {
      this.status('Membuka kamera telefon anda…');
      this.nativeInput.click();
      return;
    }

    this.status('Meminta kebenaran kamera…');

    navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1920 } },
      audio: false
    }).then(function (stream) {
      self.stream = stream;
      self.video.srcObject = stream;
      self.video.hidden = false;
      self.placeholder.hidden = true;
      self.video.play();
      self.setButtons('live');
      self.status('');
    }).catch(function () {
      self.status('Tidak dapat buka kamera dalam laman. Kami buka kamera telefon anda pula.');
      self.nativeInput.click();
    });
  };

  CaptureSpot.prototype.stopStream = function () {
    if (this.stream) {
      this.stream.getTracks().forEach(function (track) { track.stop(); });
      this.stream = null;
    }

    this.video.hidden = true;
    this.video.srcObject = null;
  };

  CaptureSpot.prototype.shoot = function () {
    var self = this;
    var width = this.video.videoWidth;
    var height = this.video.videoHeight;

    if (!width || !height) {
      this.status('Kamera belum bersedia. Tunggu sekejap.', 'error');
      return;
    }

    var canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.getContext('2d').drawImage(this.video, 0, 0, width, height);

    canvas.toBlob(function (blob) {
      if (!blob) {
        self.status('Gambar gagal diambil. Cuba sekali lagi.', 'error');
        return;
      }

      self.stopStream();
      self.acceptFile(new File([blob], 'momen.jpg', { type: 'image/jpeg' }), true);
    }, 'image/jpeg', 0.94);
  };

  CaptureSpot.prototype.acceptFile = function (file, alreadySized) {
    var self = this;

    if (!/^image\//.test(file.type)) {
      this.status('Fail itu bukan gambar.', 'error');
      return;
    }

    this.status('Menyediakan gambar…');
    this.stopStream();

    var prepare = alreadySized ? Promise.resolve(file) : compress(file);

    prepare.then(function (ready) {
      self.pending = ready;

      if (self.preview.src) {
        URL.revokeObjectURL(self.preview.src);
      }

      self.preview.src = URL.createObjectURL(ready);
      self.preview.hidden = false;
      self.placeholder.hidden = true;
      self.setButtons('ready');
      self.status('Nampak cantik. Hantar bila sedia.', 'ok');
    });
  };

  CaptureSpot.prototype.reset = function () {
    this.pending = null;

    if (this.preview.src) {
      URL.revokeObjectURL(this.preview.src);
    }

    this.preview.removeAttribute('src');
    this.preview.hidden = true;
    this.placeholder.hidden = false;
    this.setButtons('idle');
    this.status('');
  };

  CaptureSpot.prototype.send = function () {
    if (!this.pending) {
      return;
    }

    var name = this.nameInput ? this.nameInput.value.trim() : '';
    storeName(name);

    this.queue.push({
      blob: this.pending,
      name: name,
      caption: this.captionInput ? this.captionInput.value.trim() : '',
      attempts: 0
    });

    if (this.captionInput) {
      this.captionInput.value = '';
    }

    this.reset();
    this.renderQueue();
    this.drain();
  };

  CaptureSpot.prototype.renderQueue = function () {
    var waiting = this.queue.length + (this.sending ? 1 : 0);

    this.queueNode.setAttribute('data-active', waiting > 0 ? 'true' : 'false');

    if (waiting === 0) {
      this.queueList.innerHTML = '';
      return;
    }

    this.queueList.innerHTML =
      '<div class="queue__item"><span class="queue__dot"></span>' +
      escapeHtml(waiting + (waiting === 1 ? ' gambar sedang dihantar…' : ' gambar dalam giliran…')) +
      '</div>';
  };

  CaptureSpot.prototype.progress = function (fraction) {
    if (fraction === null) {
      this.bar.setAttribute('data-active', 'false');
      this.barFill.style.width = '0%';
      return;
    }

    this.bar.setAttribute('data-active', 'true');
    this.barFill.style.width = Math.round(fraction * 100) + '%';
  };

  /** Hantar satu demi satu; talian dewan tidak suka banyak muat naik serentak. */
  CaptureSpot.prototype.drain = function () {
    var self = this;

    if (this.sending || !this.queue.length) {
      return;
    }

    var job = this.queue.shift();
    this.sending = true;
    this.renderQueue();
    this.status('Menghantar gambar…');

    this.upload(job).then(function (photo) {
      self.sending = false;
      self.progress(null);
      self.status('Terima kasih! Gambar anda sudah masuk ke galeri.', 'ok');

      if (self.gallery && photo) {
        self.gallery.prepend(photo);
      }

      self.renderQueue();
      self.drain();
    }).catch(function (error) {
      self.sending = false;
      self.progress(null);
      job.attempts += 1;

      if (job.attempts < MAX_RETRIES) {
        // Berundur sedikit lebih lama setiap kali sebelum cuba semula.
        var wait = 1200 * job.attempts;
        self.status('Talian tersekat. Cuba semula sebentar lagi…');
        self.queue.unshift(job);
        self.renderQueue();
        window.setTimeout(function () { self.drain(); }, wait);
        return;
      }

      self.status(error.message || 'Gambar gagal dihantar. Sila cuba sekali lagi.', 'error');
      self.renderQueue();
      self.drain();
    });
  };

  CaptureSpot.prototype.upload = function (job) {
    var self = this;

    return new Promise(function (resolve, reject) {
      var form = new FormData();
      form.append('photo', job.blob, 'momen.jpg');
      form.append('guest_name', job.name);
      form.append('caption', job.caption);

      var request = new XMLHttpRequest();
      request.open('POST', config.uploadUrl, true);
      request.setRequestHeader('X-CSRF-TOKEN', config.csrf);
      request.setRequestHeader('Accept', 'application/json');
      request.timeout = 120000;

      request.upload.onprogress = function (event) {
        if (event.lengthComputable) {
          self.progress(event.loaded / event.total);
        }
      };

      request.onload = function () {
        var payload = {};

        try {
          payload = JSON.parse(request.responseText);
        } catch (e) {
          payload = {};
        }

        if (request.status >= 200 && request.status < 300) {
          resolve(payload.photo);
          return;
        }

        // 4xx bermakna gambar itu sendiri bermasalah — mencuba semula tidak membantu.
        var message = payload.message || 'Gambar gagal dihantar.';

        if (request.status >= 400 && request.status < 500) {
          job.attempts = MAX_RETRIES;
        }

        reject(new Error(message));
      };

      request.onerror = function () { reject(new Error('Talian terputus.')); };
      request.ontimeout = function () { reject(new Error('Talian terlalu perlahan.')); };

      request.send(form);
    });
  };

  /* ------------------------------------------------------------- galeri */

  function Gallery(root) {
    this.root = root;
    this.grid = el('[data-grid]', root);
    this.empty = el('[data-empty]', root);
    this.moreBtn = el('[data-more]', root);
    this.countNode = el('[data-count]', root);

    this.oldestId = Number(this.grid.getAttribute('data-oldest')) || 0;
    this.newestId = Number(this.grid.getAttribute('data-newest')) || 0;
    this.loading = false;
    this.seen = {};

    var self = this;

    Array.prototype.forEach.call(this.grid.children, function (node) {
      self.seen[node.getAttribute('data-id')] = true;
    });

    if (this.moreBtn) {
      this.moreBtn.addEventListener('click', function () { self.loadOlder(); });
    }

    this.setupLightbox();
    this.poll();
  }

  /** `fresh` menandakan gambar yang baru tiba semasa majlis, supaya hanya
   *  gambar itu diperkenalkan dengan animasi dan bukan seluruh galeri. */
  Gallery.prototype.card = function (photo, fresh) {
    var ratio = photo.width && photo.height ? (photo.height / photo.width) : 1.25;
    var caption = '';

    if (photo.name || photo.caption) {
      caption =
        '<div class="shot__caption">' +
        (photo.name ? '<div class="shot__name">' + escapeHtml(photo.name) + '</div>' : '') +
        (photo.caption ? '<div class="shot__text">' + escapeHtml(photo.caption) + '</div>' : '') +
        '</div>';
    }

    return (
      '<figure class="shot' + (fresh ? ' shot--fresh' : '') + '"' +
      ' data-id="' + photo.id + '" data-full="' + escapeHtml(photo.original) + '"' +
      ' data-by="' + escapeHtml(photo.name || '') + '">' +
      '<img src="' + escapeHtml(photo.thumb) + '" alt="Momen dirakam oleh ' + escapeHtml(photo.name || 'tetamu') + '"' +
      ' loading="lazy" style="aspect-ratio:1/' + ratio.toFixed(3) + '">' +
      caption +
      '</figure>'
    );
  };

  Gallery.prototype.prepend = function (photo) {
    if (!photo || this.seen[photo.id]) {
      return;
    }

    this.seen[photo.id] = true;
    this.grid.insertAdjacentHTML('afterbegin', this.card(photo, true));
    this.newestId = Math.max(this.newestId, photo.id);

    if (this.empty) {
      this.empty.hidden = true;
    }

    this.bumpCount(1);
  };

  Gallery.prototype.append = function (photo) {
    if (!photo || this.seen[photo.id]) {
      return;
    }

    this.seen[photo.id] = true;
    this.grid.insertAdjacentHTML('beforeend', this.card(photo, false));
    this.oldestId = photo.id;
  };

  Gallery.prototype.bumpCount = function (delta) {
    if (!this.countNode) {
      return;
    }

    var current = Number(this.countNode.getAttribute('data-value')) || 0;
    this.setCount(current + delta);
  };

  Gallery.prototype.setCount = function (value) {
    if (!this.countNode) {
      return;
    }

    this.countNode.setAttribute('data-value', value);
    this.countNode.textContent = value === 0
      ? 'Belum ada gambar lagi'
      : value + ' momen dikongsi setakat ini';
  };

  Gallery.prototype.loadOlder = function () {
    var self = this;

    if (this.loading) {
      return;
    }

    this.loading = true;
    this.moreBtn.disabled = true;
    this.moreBtn.textContent = 'Memuatkan…';

    fetch(config.feedUrl + '?before=' + this.oldestId, { headers: { Accept: 'application/json' } })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        (data.photos || []).forEach(function (photo) { self.append(photo); });

        self.moreBtn.hidden = !data.has_more;
        self.moreBtn.disabled = false;
        self.moreBtn.textContent = 'Lihat lagi';
        self.loading = false;
      })
      .catch(function () {
        self.moreBtn.disabled = false;
        self.moreBtn.textContent = 'Cuba lagi';
        self.loading = false;
      });
  };

  /** Galeri menyegar sendiri supaya tetamu nampak gambar orang lain masuk. */
  Gallery.prototype.poll = function () {
    var self = this;

    window.setInterval(function () {
      if (document.hidden) {
        return;
      }

      fetch(config.sinceUrl + '?after=' + self.newestId, { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          (data.photos || []).forEach(function (photo) { self.prepend(photo); });

          if (typeof data.total === 'number') {
            self.setCount(data.total);
          }
        })
        .catch(function () { /* Talian sekejap hilang; cuba lagi pusingan depan. */ });
    }, 15000);
  };

  Gallery.prototype.setupLightbox = function () {
    var box = el('[data-lightbox]');

    if (!box) {
      return;
    }

    var image = el('img', box);
    var bar = el('[data-lightbox-bar]', box);

    var close = function () {
      box.setAttribute('data-open', 'false');
      image.removeAttribute('src');
      document.body.style.overflow = '';
    };

    this.grid.addEventListener('click', function (event) {
      var shot = event.target.closest('.shot');

      if (!shot) {
        return;
      }

      image.src = shot.getAttribute('data-full');
      bar.textContent = shot.getAttribute('data-by')
        ? 'Dirakam oleh ' + shot.getAttribute('data-by')
        : '';
      box.setAttribute('data-open', 'true');
      document.body.style.overflow = 'hidden';
    });

    el('[data-lightbox-close]', box).addEventListener('click', close);

    box.addEventListener('click', function (event) {
      if (event.target === box) {
        close();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        close();
      }
    });
  };

  /* --------------------------------------------------------------- mula */

  document.addEventListener('DOMContentLoaded', function () {
    setupMotion();
    setupPetals();

    var galleryRoot = el('[data-gallery]');
    var gallery = galleryRoot ? new Gallery(galleryRoot) : null;
    var captureRoot = el('[data-capture]');

    if (captureRoot) {
      new CaptureSpot(captureRoot, gallery);
    }
  });
})();
