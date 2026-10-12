<?php

namespace App\Services\Tree;

use App\Models\Company;
use App\Models\Folder;
use App\Models\FolderTemplate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tạo công ty và dự án: vừa tạo dữ liệu nghiệp vụ vừa tạo node trong cây.
 */
class DirectoryService
{
    public function __construct(private FolderTreeService $tree) {}

    /**
     * @param  array{name: string, tax_code?: string|null, country?: string|null}  $data
     */
    public function createCompany(array $data, ?User $user = null): Company
    {
        return DB::transaction(function () use ($data, $user) {
            $company = Company::create($data);

            $this->tree->createChild(
                $this->tree->companyRoot(),
                $company->name,
                Folder::KIND_COMPANY,
                ['company_id' => $company->id, 'is_system' => true],
                $user,
            );

            return $company;
        });
    }

    public function createProject(Company $company, string $name, ?User $user = null): Project
    {
        return DB::transaction(function () use ($company, $name, $user) {
            $project = $company->projects()->create(['name' => $name]);

            $folder = $this->tree->createChild(
                $company->folder()->firstOrFail(),
                $project->name,
                Folder::KIND_PROJECT,
                ['project_id' => $project->id, 'is_system' => true],
                $user,
            );

            $this->tree->applyTemplate($folder, FolderTemplate::PROJECT, $user);

            return $project;
        });
    }
}
