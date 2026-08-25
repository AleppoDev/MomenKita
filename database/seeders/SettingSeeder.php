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
            'groom_name' => 'Ikhmal Hafiz',
            'couple_slug' => 'Ikhmal-Aisyah',
            'wedding_date' => '12 Disember 2026',
            'venue_name' => '"Terbitnya jejaka dari bumi tempat beralas tapak, melangkah gagah menyunting bunga."',
            'venue_address' => 'Besut, Terengganu',
            'hashtag' => '#MalSyah',
            'hero_note' => 'Terima kasih kerana sudi hadir meraikan hari bahagia kami.',
            'camera_note' => 'Rakam momen yang anda nampak — gambar anda terus masuk ke galeri kami.',
        ]);
    }
}
