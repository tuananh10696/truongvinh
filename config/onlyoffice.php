<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ONLYOFFICE Docs
    |--------------------------------------------------------------------------
    |
    | public_url: địa chỉ trình duyệt dùng để tải editor (api.js).
    | app_internal_url: địa chỉ mà Document Server dùng để gọi về Laravel
    |   (tải file, gửi callback lưu). Để trống thì dùng APP_URL.
    | server_internal_url: địa chỉ Laravel dùng để tải file đã sửa từ
    |   Document Server. Để trống thì dùng nguyên URL trong callback.
    |
    */

    'public_url' => rtrim((string) env('ONLYOFFICE_PUBLIC_URL', 'http://localhost:8080'), '/'),

    'app_internal_url' => rtrim((string) env('ONLYOFFICE_APP_INTERNAL_URL', env('APP_URL', 'http://localhost')), '/'),

    'server_internal_url' => rtrim((string) env('ONLYOFFICE_SERVER_INTERNAL_URL', ''), '/'),

    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),

    'jwt_header' => env('ONLYOFFICE_JWT_HEADER', 'Authorization'),

    /** Thời hạn (phút) của link tải file cấp cho Document Server. */
    'download_ttl' => (int) env('ONLYOFFICE_DOWNLOAD_TTL', 10),

];
