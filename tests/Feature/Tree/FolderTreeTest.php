<?php

namespace Tests\Feature\Tree;

use App\Models\Folder;
use App\Models\User;
use App\Services\Tree\DirectoryService;
use App\Services\Tree\FolderTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_tree_matches_client_diagram(): void
    {
        $tree = app(FolderTreeService::class);

        $root = Folder::query()->where('kind', Folder::KIND_ROOT)->sole();
        $this->assertSame(['Trường Vinh', 'Công ty'], $root->children()->pluck('name')->all());

        $this->assertSame(
            ['Hợp đồng', 'Kế toán', 'Thương mại', 'Du học', 'Tổng hợp'],
            $tree->truongVinh()->children()->pluck('name')->all(),
        );
    }

    public function test_creating_a_project_creates_the_seven_template_folders(): void
    {
        $directory = app(DirectoryService::class);

        $company = $directory->createCompany(['name' => 'Công ty A']);
        $project = $directory->createProject($company, 'Dự án 1');

        $folder = $project->folder()->sole();
        $this->assertSame(Folder::KIND_PROJECT, $folder->kind);
        $this->assertSame($company->folder()->sole()->id, $folder->parent_id);
        $this->assertSame([
            'Đầu tư',
            'HĐDV (TV)',
            'Các HĐ tổng hợp của DA',
            'PCCC',
            'Môi trường',
            'Tài chính (Thuế, BH, Ngân hàng)',
            'Tổng hợp',
        ], $folder->children()->pluck('name')->all());

        $tab = $folder->children()->first();
        $this->assertSame($folder->path.$tab->id.'/', $tab->path);
        $this->assertSame($project->id, $tab->project_id);
    }

    public function test_project_tab_url_renders_project_page_with_that_tab_active(): void
    {
        $directory = app(DirectoryService::class);
        $project = $directory->createProject($directory->createCompany(['name' => 'Công ty A']), 'Dự án 1');
        $pccc = $project->folder()->sole()->children()->where('name', 'PCCC')->sole();

        $this->actingAs(User::factory()->create())
            ->get(route('folders.show', $pccc))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Folders/Project')
                ->where('activeTab.id', $pccc->id)
                ->has('tabs', 7));
    }

    public function test_users_can_add_companies_and_projects_from_the_ui(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('companies.store'), ['name' => 'Công ty D'])->assertRedirect();
        $company = Folder::query()->where('kind', Folder::KIND_COMPANY)->where('name', 'Công ty D')->sole();

        $this->actingAs($user)->post(route('projects.store', $company->company_id), ['name' => 'Nhà máy 1'])->assertRedirect();
        $this->assertSame(7, Folder::query()->where('kind', Folder::KIND_PROJECT)->sole()->children()->count());
    }

    public function test_system_folders_cannot_be_renamed(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch(route('folders.update', app(FolderTreeService::class)->truongVinh()), ['name' => 'Khác'])
            ->assertForbidden();
    }
}
