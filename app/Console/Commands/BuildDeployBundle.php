<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Menyusun pemasangan sedia muat naik untuk hosting perkongsian.
 *
 * Hosting tanpa SSH tidak boleh menjalankan composer, migrasi, atau
 * storage:link, jadi semuanya mesti dibungkus terlebih dahulu dan struktur
 * foldernya mesti sudah betul sebelum fail pertama dimuat naik.
 */
class BuildDeployBundle extends Command
{
    protected $signature = 'momenkita:bundle {--out=deploy/bundle : Folder keluaran}';

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

        $appRoot = $outPath . '/app-root';
        $htdocs = $outPath . '/htdocs';

        $copied = $this->copyApp($base, $appRoot);
        $this->components->info('Fail aplikasi disalin: ' . $copied);

        // public/storage ialah junction yang dicipta oleh storage:link. Pada
        // Windows is_link() tidak mengenalinya, dan pengulang cuba menyalin
        // folder itu sebagai fail. Ia dikecualikan terus: struktur folder
        // muat naik dicipta semula sebagai folder sebenar di bawah.
        $public = $this->copyTree($base . '/public', $htdocs, '', ['storage']);
        $this->components->info('Fail awam disalin ke htdocs: ' . $public);

        /*
         | Muat naik ditulis terus ke dalam akar web, jadi tiada symlink
         | diperlukan. Folder mesti wujud dahulu kerana banyak hosting
         | perkongsian tidak membenarkan PHP mencipta folder pada aras ini.
         */
        foreach (['photos', 'thumbs', 'majlis'] as $dir) {
            @mkdir($htdocs . '/storage/' . $dir, 0755, true);
        }

        @mkdir($appRoot . '/storage/app/private/archives', 0755, true);
        @mkdir($appRoot . '/storage/logs', 0755, true);

        foreach (['cache/data', 'sessions', 'views'] as $dir) {
            @mkdir($appRoot . '/storage/framework/' . $dir, 0755, true);
        }

        file_put_contents($outPath . '/.env.pengeluaran', $this->envTemplate());
        file_put_contents($outPath . '/LANGKAH.md', $this->steps());

        $this->newLine();
        $this->components->info('Pakej siap: ' . $out);
        $this->line('  <fg=gray>app-root/</>        naik ke akar akaun, di luar htdocs');
        $this->line('  <fg=gray>htdocs/</>          naik ke akar web');
        $this->line('  <fg=gray>.env.pengeluaran</> isi, namakan .env, letak dalam akar akaun');
        $this->line('  <fg=gray>LANGKAH.md</>       urutan penuh');

        return self::SUCCESS;
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

            /*
             | public/storage ialah symlink yang dicipta oleh storage:link.
             | Ia tidak boleh disalin, dan tidak sepatutnya: pakej ini menulis
             | muat naik terus ke dalam akar web, jadi struktur folder itu
             | dicipta semula sebagai folder sebenar selepas penyalinan.
             */
            if (is_link($item->getPathname())) {
                continue;
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
            '# Muat naik ditulis terus ke dalam akar web, jadi storage:link tidak',
            '# diperlukan. Laluan ini relatif kepada akar aplikasi.',
            'PUBLIC_DISK_ROOT=htdocs/storage',
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

Susunan folder yang dituju:

```
akar akaun/
  app/  bootstrap/  config/  vendor/  ...   <- daripada app-root/
  .env                                      <- daripada .env.pengeluaran
  htdocs/                                   <- akar web
    index.php  css/  js/  storage/
```

Kunci keselamatannya ialah `.env` dan `vendor/` berada **di luar** `htdocs/`.
Kalau ia berada di dalam, sesiapa boleh membaca kata laluan pangkalan data
anda melalui pelayar.

## Langkah

1. **Pangkalan data.** Cipta satu dalam panel hosting. Catat nama, pengguna,
   kata laluan dan hos.

2. **Import skema.** Buka phpMyAdmin, pilih pangkalan data itu, import
   `momenkita.sql`. Ini menggantikan `php artisan migrate`, yang tidak boleh
   dijalankan tanpa SSH.

3. **Sediakan .env.** Isi `.env.pengeluaran`: butiran pangkalan data dan
   `APP_URL`. Untuk `APP_KEY`, jalankan pada mesin anda:

   ```
   php artisan key:generate --show
   ```

   Salin nilai itu masuk. Untuk kata laluan admin:

   ```
   php artisan momenkita:password
   ```

   Namakan fail itu `.env` dan letak di akar akaun.

4. **Muat naik.** Isi `app-root/` ke akar akaun, isi `htdocs/` ke `htdocs/`.
   Guna ZIP dan ekstrak melalui pengurus fail hosting kalau boleh; melalui FTP
   fail demi fail ini mengambil masa lama kerana `vendor/` sahaja mengandungi
   hampir 9,000 fail.

5. **Kebenaran folder.** Pastikan boleh ditulis (755 atau 775):
   - `storage/` dan semua isinya
   - `bootstrap/cache/`
   - `htdocs/storage/`

6. **SSL.** Hidupkan sijil percuma dalam panel hosting. Ini bukan pilihan:
   kamera pelayar langsung tidak wujud atas HTTP, jadi tetamu tidak akan dapat
   mengambil gambar.

7. **Cron web.** Tetapkan cron hosting memanggil URL `/cron/TOKEN` dalam `.env`
   anda, setiap 5 minit. Tanpanya muat turun ZIP tidak akan pernah siap kerana
   tiada pekerja queue yang berjalan.

## Semakan selepas naik

- Buka laman utama; nama pengantin sepatutnya kelihatan
- Buka `/admin`, log masuk, pastikan **tiada** amaran merah tentang HTTPS
- Muat naik satu gambar dari telefon
- Tekan "Sediakan ZIP", tunggu cron berjalan, pastikan ia bertukar kepada siap

## Had yang perlu diterima

Hosting perkongsian percuma mengehadkan CPU dan permintaan serentak. Untuk
majlis sebenar dengan ratusan tetamu memuat naik pada masa yang sama, ini akan
tersekat atau digantung. Gunakan hosting percuma untuk demo; gunakan VPS untuk
majlis yang dibayar.
MD;
    }
}
