<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Tree\DirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thêm công ty khách hàng và dự án (kèm 7 thư mục mặc định).
 */
class DirectoryController extends Controller
{
    public function __construct(private DirectoryService $directory) {}

    public function storeCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
        ], attributes: ['name' => 'tên công ty', 'tax_code' => 'mã số thuế', 'country' => 'quốc gia']);

        $this->directory->createCompany($data, $request->user());

        $this->toast('Đã thêm công ty.');

        return back();
    }

    public function storeProject(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ], attributes: ['name' => 'tên dự án']);

        $this->directory->createProject($company, $data['name'], $request->user());

        $this->toast('Đã thêm dự án.');

        return back();
    }
}
