<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GenerateAdminPassword extends Command
{
    protected $signature = 'momenkita:password
                            {password? : Kata laluan pilihan anda; kosongkan untuk jana yang kuat}';

    protected $description = 'Jana cincangan kata laluan admin untuk ditampal ke dalam .env';

    public function handle(): int
    {
        $password = (string) $this->argument('password');
        $generated = $password === '';

        if ($generated) {
            $password = $this->makePassword();
        } elseif (strlen($password) < 12) {
            $this->components->warn('Kata laluan itu pendek. Panjang sekurang-kurangnya 12 aksara jauh lebih selamat.');
        }

        $hash = Hash::make($password);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Kata laluan</>', "<fg=green;options=bold>{$password}</>");
        $this->newLine();
        $this->line('Tampal baris ini ke dalam .env:');
        $this->newLine();
        $this->line("  <fg=yellow>ADMIN_PASSWORD_HASH=\"{$hash}\"</>");
        $this->line('  <fg=yellow>ADMIN_PASSWORD=</>');
        $this->newLine();
        $this->components->info('Kosongkan ADMIN_PASSWORD supaya hanya cincangan digunakan.');

        if ($generated) {
            $this->components->warn('Simpan kata laluan di atas sekarang. Ia tidak disimpan di mana-mana dan tidak boleh dipulangkan dari cincangan.');
        }

        return self::SUCCESS;
    }

    /** Empat kumpulan pendek: cukup rawak, tetapi masih boleh dibaca melalui telefon. */
    private function makePassword(): string
    {
        // Tiada 0/O/1/l/I: pengantin selalunya membaca kata laluan ini kepada
        // orang lain, dan aksara yang serupa menyebabkan salah taip.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $groups = [];

        for ($group = 0; $group < 4; $group++) {
            $chunk = '';

            for ($i = 0; $i < 4; $i++) {
                $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $groups[] = $chunk;
        }

        return implode('-', $groups);
    }
}
