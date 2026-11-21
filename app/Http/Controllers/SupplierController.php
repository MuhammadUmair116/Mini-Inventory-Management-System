<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a paginated, filterable supplier list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Supplier::class);

        $search = trim((string) $request->input('search', ''));
        $isActive = $request->input('is_active');

        $suppliers = Supplier::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('contact_person', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->when(
                in_array($isActive, ['1', '0'], true),
                fn (Builder $query) => $query->where('is_active', (bool) $isActive)
            )
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'is_active' => $isActive,
            ],
            'stats' => [
                'total' => Supplier::query()->count(),
                'active' => Supplier::active()->count(),
                'inactive' => Supplier::query()->where('is_active', false)->count(),
                'with_purchases' => Supplier::has('purchases')->count(),
            ],
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::query()->create($request->validated());

        return to_route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the given supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return to_route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the given supplier when it has no purchase history.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        if ($supplier->purchases()->exists()) {
            return to_route('suppliers.index')
                ->with('error', 'Supplier has purchase records and cannot be deleted.');
        }

        $supplier->delete();

        return to_route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
