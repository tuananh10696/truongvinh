<?php

namespace App\Http\Middleware;

use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'navigation' => fn () => $request->user() ? $this->navigation($request) : null,
        ];
    }

    /**
     * Dữ liệu cho thanh bên: các nhánh gốc và thư mục đã ghim.
     *
     * @return array<string, mixed>
     */
    private function navigation(Request $request): array
    {
        $roots = Folder::query()
            ->whereIn('kind', [Folder::KIND_TRUONG_VINH, Folder::KIND_COMPANY_ROOT])
            ->pluck('id', 'kind');

        $pins = $request->user()->pinnedFolders()->with('parent:id,name')->orderBy('folder_pins.created_at')->get();

        return [
            'truongVinhId' => $roots[Folder::KIND_TRUONG_VINH] ?? null,
            'companyRootId' => $roots[Folder::KIND_COMPANY_ROOT] ?? null,
            'pins' => $pins->map(fn (Folder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent' => $folder->parent?->name,
            ]),
        ];
    }
}
