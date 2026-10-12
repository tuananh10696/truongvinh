<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nơi lưu file
    |--------------------------------------------------------------------------
    |
    | Disk trong config/filesystems.php dùng để lưu file và các phiên bản.
    | Mặc định là ổ đĩa của server (storage/app/private).
    |
    */

    'disk' => env('DOCUMENTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Dung lượng tối đa mỗi file tải lên (KB)
    |--------------------------------------------------------------------------
    */

    'max_upload_kb' => (int) env('DOCUMENTS_MAX_UPLOAD_KB', 51200),

];
