<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a paginated, filterable customer list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Customer::class);

        $search = trim((string) $request->input('search', ''));
        $isActive = $request->input('is_active');

        $customers = Customer::query()
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

        return Inertia::render('MasterData/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'is_active' => $isActive,
            ],
            'stats' => [
                'total' => Customer::query()->count(),
                'active' => Customer::active()->count(),
                'inactive' => Customer::query()->where('is_active', false)->count(),
                'with_sales' => Customer::has('sales')->count(),
            ],
            'can' => [
                'create' => Gate::allows('create', Customer::class),
                'update' => Gate::allows('update', new Customer),
                'delete' => Gate::allows('delete', new Customer),
            ],
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::query()->create($request->validated());

        return to_route('customers.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Update the given customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the given customer when it has no sales history.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        if ($customer->sales()->exists()) {
            return to_route('customers.index')
                ->with('error', 'Customer has sales records and cannot be deleted.');
        }

        $customer->delete();

        return to_route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
