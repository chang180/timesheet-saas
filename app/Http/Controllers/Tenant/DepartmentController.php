<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDepartmentRequest;
use App\Http\Requests\Tenant\UpdateDepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Company $company): JsonResponse
    {
        $query = Department::query()
            ->where('company_id', $company->getKey())
            ->with('division');

        if (request()->has('division_id')) {
            $query->where('division_id', request('division_id'));
        }

        $departments = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'departments' => $departments->map(fn ($department) => [
                'id' => $department->id,
                'division_id' => $department->division_id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'sort_order' => $department->sort_order,
                'is_active' => (bool) $department->is_active,
            ]),
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(StoreDepartmentRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        if (! isset($data['slug']) || $data['slug'] === '') {
            $data['slug'] = Str::slug($data['name']);
        }

        Department::create([
            'company_id' => $company->getKey(),
            'division_id' => $data['division_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.organization', $company)
            ->with('success', '部門已建立');
    }

    /**
     * Update the specified department.
     */
    public function update(UpdateDepartmentRequest $request, Company $company, Department $department): RedirectResponse
    {
        if ($department->company_id !== $company->getKey()) {
            abort(404);
        }

        $data = $request->validated();

        if (isset($data['name']) && (! isset($data['slug']) || $data['slug'] === '')) {
            $data['slug'] = Str::slug($data['name']);
        }

        $department->update($data);

        return redirect()->route('tenant.organization', $company)
            ->with('success', '部門已更新');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Company $company, Department $department): RedirectResponse
    {
        if ($department->company_id !== $company->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $department);

        if ($department->users()->exists()) {
            return redirect()->route('tenant.organization', $company)
                ->with('error', '無法刪除部門，因為已有成員資料。請先遷移成員。');
        }

        if ($department->teams()->exists()) {
            return redirect()->route('tenant.organization', $company)
                ->with('error', '無法刪除部門，因為已有小組資料。請先刪除或遷移小組。');
        }

        $department->delete();

        return redirect()->route('tenant.organization', $company)
            ->with('success', '部門已刪除');
    }
}
