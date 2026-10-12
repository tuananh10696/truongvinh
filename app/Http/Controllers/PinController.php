<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PinController extends Controller
{
    /**
     * Ghim hoặc bỏ ghim thư mục trên thanh bên.
     */
    public function toggle(Request $request, Folder $folder): RedirectResponse
    {
        $result = $request->user()->pinnedFolders()->toggle($folder->id);
        $this->toast($result['attached'] !== [] ? 'Đã ghim thư mục.' : 'Đã bỏ ghim thư mục.');

        return back();
    }
}
