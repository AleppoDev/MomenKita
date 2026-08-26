<?php

return [
    /*
     | Panel admin dilindungi satu kata laluan; tiada pendaftaran pengguna
     | kerana hanya pengantin dan keluarga terdekat perlu masuk.
     |
     | Utamakan cincangan. Kata laluan teks biasa dalam .env bermakna sesiapa
     | yang membaca fail itu terus memilikinya; cincangan tidak boleh
     | dipulangkan. Jana dengan: php artisan momenkita:password
     */
    'admin_password_hash' => env('ADMIN_PASSWORD_HASH', ''),

    'admin_password' => env('ADMIN_PASSWORD', ''),

    /*
     | Token untuk laluan cron web. Hanya diperlukan pada hosting tanpa akses
     | baris arahan; biarkan kosong pada pelayan yang menjalankan queue:work
     | sebenar, dan laluan itu akan mengembalikan 404.
     */
    'cron_token' => env('CRON_TOKEN', ''),

    'cron_seconds' => env('CRON_SECONDS', 20),
];
