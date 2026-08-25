<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::putMany([
            'bride_name' => 'Nur Aisyah',
            'groom_name' => 'Ahmad Firdaus',
            'couple_slug' => 'aisyah-firdaus',
            'wedding_date' => '12 Disember 2026',
            'venue_name' => 'Dewan Seri Melati',
            'venue_address' => 'Kuala Terengganu, Terengganu',
            'hashtag' => '#AisyahFirdaus',
            'hero_note' => 'Terima kasih kerana sudi hadir meraikan hari bahagia kami.',
            'camera_note' => 'Rakam momen yang anda nampak — gambar anda terus masuk ke galeri kami.',
        ]);
    }
}
