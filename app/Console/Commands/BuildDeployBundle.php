<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Menyusun pemasangan sedia muat naik untuk hosting perkongsian.
 *
 * Susunan Laravel yang biasa meletakkan aplikasi di atas akar web dan hanya
 * public/ di dalamnya. Banyak hosting percuma tidak membenarkannya sama
 * sekali: InfinityFree memadam fail di luar htdocs secara automatik, dan
 * open_basedir menghalang PHP daripada menulis di sana.
 *
 * Jadi semuanya duduk di dalam akar web, dan bahagian yang tidak sepatutnya
 * dicapai melalui pelayar dilindungi oleh .htaccess.
 */
class BuildDeployBundle extends Command
{
    protected $signature = 'momenkita:bundle
                            {--out=deploy/bundle : Folder keluaran}
                            {--no-zip : Langkau pemampatan}';

    protected $description = 'Bina pakej sedia muat naik untuk hosting perkongsian tanpa SSH';

    /** Folder yang tidak sepatutnya sampai ke pelayan langsung. */
    private const SKIP = [
        '.git', '.github', 'node_modules', 'tests', 'deploy',
        'sales', '.claude', 'storage/logs',
        'storage/framework/cache/data', 'storage/framework/sessions',
        'storage/framework/views', 'storage/app/public', 'storage/app/private',
    ];

    private const SKIP_FILES = [
        '.env', '.env.backup', '.phpunit.result.cache', 'phpunit.xml',
        '.gitignore', '.gitattributes', '.editorconfig',
    ];

    /** Nama folder aplikasi di dalam akar web. */
    private const APP_DIR = 'laravel';

    public function handle(): int
    {
        $base = base_path();
        $out = (string) $this->option('out');

        $outPath = (Str::startsWith($out, ['/', '\\']) || preg_match('#^[A-Za-z]:#', $out))
            ? $out
            : $base . DIRECTORY_SEPARATOR . $out;

        if (is_dir($outPath)) {
            $this->components->warn('Membuang keluaran terdahulu: ' . $out);
            $this->deleteTree($outPath);
        }

        $htdocs = $outPath . '/htdocs';
        $appDir = $htdocs . '/' . self::APP_DIR;

        $copied = $this->copyApp($base, $appDir);
        $this->components->info('Fail aplikasi disalin: ' . $copied);

        /*
         | public/storage ialah junction daripada storage:link. Pada Windows
         | is_link() tidak mengenalinya dan pengulang cuba menyalinnya sebagai
         | fail, jadi ia dikecualikan dan dicipta semula sebagai folder sebenar.
         */
        $public = $this->copyTree($base . '/public', $htdocs, '', ['storage']);
        $this->components->info('Fail awam disalin: ' . $public);

        $this->writeIndex($htdocs);
        $this->protectApp($appDir);

        /*
         | Setiap folder ini mesti wujud sebelum Laravel boleh melayan satu
         | permintaan pun; tanpa storage/framework/views, Blade tidak boleh
         | dikompil dan setiap halaman menjadi 500.
         |
         | Ia mesti mengandungi sekurang-kurangnya satu fail. Pengekstrak ZIP
         | melangkau entri folder kosong, jadi folder yang benar-benar kosong
         | tidak sampai ke pelayan walaupun ia ada di dalam arkib.
         */
        $required = [
            $htdocs . '/storage/photos',
            $htdocs . '/storage/thumbs',
            $htdocs . '/storage/majlis',
            $appDir . '/storage/logs',
            $appDir . '/storage/app/private/archives',
            $appDir . '/storage/framework/cache/data',
            $appDir . '/storage/framework/sessions',
            $appDir . '/storage/framework/views',
        ];

        foreach ($required as $dir) {
            @mkdir($dir, 0755, true);
            file_put_contents($dir . '/.gitkeep', '');
        }

        file_put_contents($outPath . '/.env.pengeluaran', $this->envTemplate());
        file_put_contents($outPath . '/LANGKAH.md', $this->steps());

        if (! $this->option('no-zip')) {
            $this->zip($htdocs, $outPath . '/htdocs.zip');
        }

        $this->newLine();
        $this->components->info('Pakej siap: ' . $out);
        $this->line('  <fg=gray>htdocs.zip</>        ekstrak DI DALAM htdocs');
        $this->line('  <fg=gray>.env.pengeluaran</>  isi, namakan .env, letak dalam htdocs/' . self::APP_DIR . '/');
        $this->line('  <fg=gray>LANGKAH.md</>        urutan penuh');

        return self::SUCCESS;
    }

    /**
     * index.php Laravel menjangka aplikasi berada satu aras di atasnya. Di
     * sini ia berada di dalam subfolder, jadi laluannya diubah.
     */
    private function writeIndex(string $htdocs): void
    {
        $index = $htdocs . '/index.php';
        $source = (string) file_get_contents($index);

        $source = str_replace(
            ["__DIR__.'/../storage", "__DIR__.'/../vendor", "__DIR__.'/../bootstrap"],
            [
                "__DIR__.'/" . self::APP_DIR . "/storage",
                "__DIR__.'/" . self::APP_DIR . "/vendor",
                "__DIR__.'/" . self::APP_DIR . "/bootstrap",
            ],
            $source
        );

        file_put_contents($index, $source);
    }

    /**
     * Kod aplikasi, kebergantungan dan .env berada di dalam akar web kerana
     * hosting ini tidak membenarkan apa-apa di luarnya. Satu-satunya perkara
     * yang menghalangnya daripada dicapai melalui pelayar ialah fail ini.
     */
    private function protectApp(string $appDir): void
    {
        $rules = <<<'HTACCESS'
# Folder ini mengandungi .env, vendor dan seluruh kod aplikasi.
# Tiada satu pun sepatutnya boleh dicapai melalui pelayar.

# Apache 2.4
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

# Apache 2.2
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
HTACCESS;

        file_put_contents($appDir . '/.htaccess', $rules . "\n");
    }

    private function copyApp(string $from, string $to): int
    {
        $count = 0;

        foreach (scandir($from) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'public') {
                continue;
            }

            if (in_array($entry, self::SKIP, true) || in_array($entry, self::SKIP_FILES, true)) {
                continue;
            }

            $source = $from . '/' . $entry;

            if (is_dir($source)) {
                $count += $this->copyTree($source, $to . '/' . $entry, $entry);

                continue;
            }

            @mkdir($to, 0755, true);
            copy($source, $to . '/' . $entry);
            $count++;
        }

        return $count;
    }

    /** @param  list<string>  $exclude  Nama pada aras teratas yang dilangkau */
    private function copyTree(string $from, string $to, string $prefix = '', array $exclude = []): int
    {
        if (! is_dir($from)) {
            return 0;
        }

        @mkdir($to, 0755, true);
        $count = 0;

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            /** @var SplFileInfo $item */
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($from) + 1));
            $check = $prefix !== '' ? $prefix . '/' . $relative : $relative;

            foreach (self::SKIP as $skip) {
                if ($check === $skip || Str::startsWith($check, $skip . '/')) {
                    continue 2;
                }
            }

            foreach ($exclude as $skip) {
                if ($relative === $skip || Str::startsWith($relative, $skip . '/')) {
                    continue 2;
                }
            }

            $target = $to . '/' . $relative;

            if ($item->isDir()) {
                @mkdir($target, 0755, true);

                continue;
            }

            @mkdir(dirname($target), 0755, true);
            copy($item->getPathname(), $target);
            $count++;
        }

        return $count;
    }

    /**
     * Memampat dengan pemisah laluan garis miring ke hadapan.
     *
     * Compress-Archive pada Windows PowerShell 5.1 menulis backslash sebagai
     * pemisah di dalam arkib. Pengekstrak Linux tidak menganggapnya sebagai
     * pemisah folder, jadi arkib itu diekstrak menjadi ribuan fail rata dan
     * aplikasi langsung tidak berjalan.
     */
    private function zip(string $source, string $zipPath): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->components->warn('Sambungan zip tidak aktif; muat naik folder terus.');

            return;
        }

        $archive = new ZipArchive();

        if ($archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->components->warn('Tidak dapat mencipta ' . basename($zipPath));

            return;
        }

        $separator = chr(92);
        $root = rtrim(strtr((string) realpath($source), $separator, '/'), '/');

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $path = strtr($item->getPathname(), $separator, '/');
            $relative = substr($path, strlen($root) + 1);

            $item->isDir()
                ? $archive->addEmptyDir($relative)
                : $archive->addFile($path, $relative);
        }

        $archive->close();

        $this->components->info(sprintf(
            '%s siap (%.1f MB)',
            basename($zipPath),
            filesize($zipPath) / 1048576
        ));
    }

    private function deleteTree(string $path): void
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($path);
    }

    private function envTemplate(): string
    {
        $token = bin2hex(random_bytes(16));

        $lines = [
            'APP_NAME=MomenKita',
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_KEY=',
            'APP_URL=https://GANTI-DENGAN-DOMAIN-ANDA',
            '',
            'APP_LOCALE=ms',
            'LOG_CHANNEL=stack',
            'LOG_LEVEL=warning',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=GANTI',
            'DB_PORT=3306',
            'DB_DATABASE=GANTI',
            'DB_USERNAME=GANTI',
            'DB_PASSWORD=GANTI',
            '',
            'SESSION_DRIVER=database',
            'SESSION_SECURE_COOKIE=true',
            'CACHE_STORE=database',
            'QUEUE_CONNECTION=database',
            'FILESYSTEM_DISK=public',
            '',
            '# Muat naik mesti berada di dalam akar web supaya boleh dihidangkan,',
            '# dan storage:link tidak boleh dijalankan tanpa SSH. Laluan ini',
            '# relatif kepada folder aplikasi, jadi ia menunjuk keluar satu aras.',
            'PUBLIC_DISK_ROOT=../storage',
            '',
            'MAIL_MAILER=log',
            '',
            '# Jana dengan: php artisan momenkita:password',
            'ADMIN_PASSWORD_HASH=',
            'ADMIN_PASSWORD=',
            '',
            '# Panggil melalui cron web hosting anda setiap 5 minit:',
            '#   https://DOMAIN-ANDA/cron/' . $token,
            'CRON_TOKEN=' . $token,
            'CRON_SECONDS=20',
            '',
        ];

        return implode("\n", $lines);
    }

    private function steps(): string
    {
        return <<<'MD'
# Deploy ke hosting perkongsian tanpa SSH

Susunan yang dituju, semuanya di dalam akar web:

```
htdocs/
  index.php  css/  js/  storage/     <- dihidangkan kepada pelayar
  laravel/                           <- dilindungi .htaccess
    .htaccess  .env  app/  vendor/  ...
```

Susunan Laravel yang biasa meletakkan `vendor/` dan `.env` di atas akar web.
Hosting ini tidak membenarkannya: fail di luar `htdocs` dipadam secara
automatik, dan `open_basedir` menghalang PHP daripada menulis di sana.

Jadi semuanya duduk di dalam `htdocs`, dan `laravel/.htaccess` yang
menghalangnya daripada dicapai melalui pelayar. **Sahkan perlindungan itu
selepas naik** — arahannya di bawah.

## Langkah

1. **Pangkalan data.** Cipta dalam panel hosting, catat hos, nama, pengguna
   dan kata laluan.

2. **Import skema.** phpMyAdmin, pilih pangkalan data itu, import
   `momenkita.sql`. Ini menggantikan `php artisan migrate`.

3. **Sediakan .env.** Isi `.env.pengeluaran`. Untuk `APP_KEY`, jalankan pada
   mesin anda:

   ```
   php artisan key:generate --show
   ```

   Untuk kata laluan admin:

   ```
   php artisan momenkita:password
   ```

   Namakan fail itu `.env`.

4. **Muat naik.** Ekstrak `htdocs.zip` **di dalam** `htdocs`. Jangan letak
   apa-apa di akar akaun; ia akan dipadam secara automatik.

5. **Letak .env** ke dalam `htdocs/laravel/`.

6. **Kebenaran folder.** Pastikan boleh ditulis (755):
   - `htdocs/laravel/storage/` dan semua isinya
   - `htdocs/laravel/bootstrap/cache/`
   - `htdocs/storage/`

7. **SSL.** Hidupkan sijil percuma. Kamera pelayar tidak wujud atas HTTP,
   jadi tanpa ini tetamu tidak akan dapat mengambil gambar.

8. **Cron web.** Panggil `/cron/TOKEN` setiap 5 minit. Tanpanya muat turun
   ZIP tidak akan pernah siap.

## Sahkan selepas naik

Buka alamat ini dalam pelayar:

```
https://domain-anda/laravel/.env
```

Anda **mesti** melihat ralat 403 atau 404. Kalau anda nampak isi fail itu,
`.htaccess` tidak berkuat kuasa dan kata laluan pangkalan data anda sedang
terdedah kepada sesiapa sahaja. Padam pemasangan itu dan tanya hosting anda
mengapa `.htaccess` diabaikan.

Kemudian:

- Laman utama memaparkan nama pengantin
- `/admin` boleh log masuk, dan **tiada** amaran merah tentang HTTPS
- Muat naik satu gambar dari telefon
- Tekan "Sediakan ZIP", tunggu cron, pastikan ia bertukar kepada siap

## Had yang perlu diterima

Hosting perkongsian percuma mengehadkan CPU dan permintaan serentak. Majlis
sebenar dengan ratusan tetamu memuat naik serentak akan tersekat atau
digantung. Gunakan hosting percuma untuk demo; gunakan VPS untuk majlis yang
dibayar.
MD;
    }
}
