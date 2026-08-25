# MomenKita

Galeri majlis perkahwinan yang dikongsi tetamu. Tetamu mengimbas kod QR, dibawa ke
halaman majlis, dan boleh terus membuka kamera untuk merakam momen — gambar masuk
ke galeri langsung dalam beberapa saat.

Dibina dengan Laravel 13. Tiada langkah build: CSS dan JavaScript ditulis tangan
dan dihidangkan terus dari `public/`, jadi tiada Node diperlukan untuk deploy.

---

## Bagaimana ia berfungsi

| Untuk | Di mana | Apa yang berlaku |
| --- | --- | --- |
| **Tetamu** | `/` | Halaman majlis bermotion. Tatal ke bawah untuk buka kamera, ambil gambar, hantar. |
| **Semua orang** | `/` (bawah) | Galeri langsung — gambar semua tetamu, menyegar sendiri setiap 15 saat. |
| **Pengantin** | `/admin` | Lihat, sembunyi, buang dan muat turun gambar dalam resolusi asal. |
| **Kod QR** | `/qr` | PNG atau SVG untuk dicetak pada kad meja dan pelamin. |

### Setiap gambar disimpan dua kali

- **Asal** — bait-demi-bait seperti yang dihantar telefon, untuk simpanan pengantin.
- **Thumbnail** — JPEG 1200px, untuk galeri supaya laju atas data mudah alih.

Thumbnail diputar mengikut EXIF (GD membuang metadata itu), manakala fail asal
dibiarkan utuh kerana penonton gambar menghormati EXIF dengan sendirinya.

Sebelum dihantar, gambar dikecilkan dalam pelayar kepada 2560px pada kualiti 0.9.
Ini memotong saiz fail sekitar tiga suku — perbezaan antara berjaya dan gagal pada
talian dewan yang sesak. Nilai ini boleh diubah di bahagian atas
[`public/js/momenkita.js`](public/js/momenkita.js).

---

## Pemasangan

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Isikan `.env`:

```
APP_URL=http://localhost:8020
DB_CONNECTION=mysql
DB_DATABASE=momenkita
ADMIN_PASSWORD=pilih-kata-laluan-yang-kuat
```

Kemudian:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=8020
```

`--seed` mengisi tetapan majlis contoh yang boleh diubah di **Admin → Tetapan**.

### Keperluan

- PHP 8.3 dengan `gd`, `exif`, `fileinfo`
- `zip` — hanya untuk muat turun ZIP pukal; tanpanya muat turun satu-satu tetap berfungsi
- MySQL 5.7 ke atas

---

## Sebelum hari majlis

Beberapa perkara yang benar-benar menentukan sama ada ia menjadi:

1. **HTTPS wajib.** `getUserMedia` tidak berfungsi atas HTTP kecuali `localhost`.
   Tanpa SSL, spot kamera mati terus di dewan. Halaman akan berundur kepada
   aplikasi kamera telefon, tetapi pengalamannya tidak sama.
2. **Kira storan.** 200 tetamu × 5 gambar × 3 MB ≈ 3 GB. Pastikan hos mencukupi.
3. **Uji kod QR sebenar.** Cetak, letak atas meja, imbas dari jarak satu meter
   dalam cahaya malap — bukan sekadar imbas dari skrin.
4. **Naikkan had PHP** jika hos ketat: `upload_max_filesize` dan `post_max_size`
   sekurang-kurangnya 30M.

## Tetapan yang boleh diubah tanpa sentuh kod

Semua di **Admin → Tetapan**: nama pengantin, tarikh, tempat, alamat, hashtag,
ucapan di halaman utama, dan ayat di ruangan kamera. Nama fail ZIP juga diambil
dari situ.

---

## Struktur

```
app/Services/PhotoStorage.php    simpan asal + jana thumbnail, betulkan EXIF
app/Http/Controllers/            halaman utama, suapan galeri, muat naik, QR, admin
public/css/app.css               halaman tetamu
public/css/admin.css             panel admin
public/js/momenkita.js           kamera, mampatan, antrian muat naik, galeri
resources/views/landing.blade.php
resources/views/admin/
```
