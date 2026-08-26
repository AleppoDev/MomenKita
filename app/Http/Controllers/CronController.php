<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memproses queue apabila dipanggil melalui HTTP.
 *
 * Hosting perkongsian tidak memberi akses baris arahan, jadi pekerja queue
 * biasa tidak boleh berjalan langsung. Tanpa ini, pembinaan arkib duduk
 * dalam jadual jobs selama-lamanya dan panel admin kekal memaparkan
 * "Sedang disediakan" tanpa penghujung.
 *
 * Pada pelayan yang mempunyai SSH, jalankan queue:work sebenar dan biarkan
 * laluan ini tidak digunakan; pekerja sebenar lebih pantas dan tidak terikat
 * dengan had masa pelaksanaan PHP.
 */
class CronController extends Controller
{
    public function __invoke(Request $request, string $token): Response
    {
        $expected = (string) config('momenkita.cron_token');

        /*
         | 404 dan bukan 403: kalau tiada token dikonfigurasi, atau token salah,
         | laluan ini sepatutnya kelihatan seperti tidak wujud. 403 mengesahkan
         | kepada penyelidik bahawa ada sesuatu di sini untuk diteka.
         */
        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(404);
        }

        /*
         | Had masa pelaksanaan PHP pada hosting perkongsian selalunya 30 saat.
         | Berhenti lebih awal daripada itu supaya pekerja menamatkan dirinya
         | dengan kemas dan bukannya dibunuh separuh jalan, yang meninggalkan
         | kerja tersangkut dalam keadaan dicuba.
         */
        $seconds = max(5, min(300, (int) config('momenkita.cron_seconds')));

        $started = microtime(true);

        try {
            Artisan::call('queue:work', [
                '--queue' => 'archives,default',
                '--stop-when-empty' => true,
                '--max-time' => $seconds,
                '--tries' => 2,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cron web gagal', ['error' => $e->getMessage()]);

            return response('gagal: ' . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        $elapsed = round(microtime(true) - $started, 1);

        return response("selesai dalam {$elapsed}s\n" . Artisan::output(), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
