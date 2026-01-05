<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDivisionRequest;
use App\Http\Requests\Tenant\UpdateDivisionRequest;
use App\Models\Company;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DivisionController extends Controller
{
    /**
     * Display a listing of divisions.
     * Note: This is kept for API compatibility but should use OrganizationController::index instead.
     */
    public function index(Company $company): JsonResponse
    {
        $divisions = Division::query()
            ->where('company_id', $company->getKey())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'divisions' => $divisions->map(fn ($division) => [
                'id' => $division->id,
                'name' => $division->name,
                'slug' => $division->slug,
                'description' => $division->description,
                'sort_order' => $division->sort_order,
                'is_active' => (bool) $division->is_active,
            ]),
        ]);
    }

    /**
     * Store a newly created division.
     */
    public function store(StoreDivisionRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        if (! isset($data['slug']) || $data['slug'] === '') {
            $data['slug'] = Str::slug($data['name']);
        }

        Division::create([
            'company_id' => $company->getKey(),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.organization', $company)
            ->with('success', '事業群已建立');
    }

    /**
     * Update the specified division.
     */
    public function update(UpdateDivisionRequest $request, Company $company, Division $division): RedirectResponse
    {
        if ($division->company_id !== $company->getKey()) {
            abort(404);
        }

        $data = $request->validated();

        if (isset($data['name']) && (! isset($data['slug']) || $data['slug'] === '')) {
            $data['slug'] = Str::slug($data['name']);
        }

        $division->update($data);

        return redirect()->route('tenant.organization', $company)
            ->with('success', '事業群已更新');
    }

    /**
     * Remove the specified division.
     */
    public function destroy(Company $company, Division $division): RedirectResponse
    {
        if ($division->company_id !== $company->getKey()) {
            abort(404);
        }

        $this->authorize('delete', $division);

        if ($division->users()->exists()) {
            return redirect()->route('tenant.organization', $company)
                ->with('error', '無法刪除事業群，因為已有成員資料。請先遷移成員。');
        }

        if ($division->departments()->exists()) {
            return redirect()->route('tenant.organization', $company)
                ->with('error', '無法刪除事業群，因為已有部門資料。請先刪除或遷移部門。');
        }

        if ($division->teams()->exists()) {
            return redirect()->route('tenant.organization', $company)
                ->with('error', '無法刪除事業群，因為已有小組資料。請先刪除或遷移小組。');
        }

        $division->delete();

        return redirect()->route('tenant.organization', $company)
            ->with('success', '事業群已刪除');
    }
}
