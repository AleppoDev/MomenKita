<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            /*
             | Hosting perkongsian tanpa SSH tidak boleh menjalankan
             | storage:link, dan banyak yang melumpuhkan symlink() sama
             | sekali. Menetapkan PUBLIC_DISK_ROOT kepada folder di dalam
             | akar web membuatkan muat naik ditulis terus ke tempat yang
             | boleh dihidangkan, tanpa symlink dan tanpa perubahan kod.
             */
            'root' => (function () {
                $root = (string) env('PUBLIC_DISK_ROOT', '');

                if ($root === '') {
                    return storage_path('app/public');
                }

                // Terima laluan relatif seperti "../storage" supaya fail .env
                // yang sama berfungsi tanpa perlu tahu laluan mutlak pelayan,
                // yang berbeza bagi setiap akaun hosting.
                $absolute = str_starts_with($root, '/') || preg_match('#^[A-Za-z]:#', $root);
                $path = $absolute ? $root : base_path($root);

                /*
                 | Segmen '..' mesti diselesaikan di sini, bukan diserahkan
                 | kepada sistem fail. open_basedir pada hosting perkongsian
                 | menolak sebarang laluan yang membawanya, walaupun laluan
                 | itu sebenarnya menunjuk ke dalam kawasan yang dibenarkan.
                 | Kegagalannya muncul sebagai muat naik yang tidak menjadi.
                 */
                $path = str_replace(chr(92), '/', $path);
                $prefix = preg_match('#^([A-Za-z]:)#', $path, $m) ? $m[1] : '';
                $segments = [];

                foreach (explode('/', substr($path, strlen($prefix))) as $segment) {
                    if ($segment === '..') {
                        array_pop($segments);
                    } elseif ($segment !== '.' && $segment !== '') {
                        $segments[] = $segment;
                    }
                }

                return $prefix . '/' . implode('/', $segments);
            })(),
            // Sengaja relatif kepada akar: gambar kekal betul walaupun laman
            // dicapai melalui domain lain, port lain, atau bertukar ke HTTPS.
            'url' => '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
