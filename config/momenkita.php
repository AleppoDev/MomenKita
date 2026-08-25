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
];
