<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Display a paginated list of units.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Unit::class);

        $search = trim((string) $request->input('search', ''));

        $units = Unit::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('short_name', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/Units/Index', [
            'units' => $units,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Store a newly created unit.
     */
    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return to_route('units.index')->with('success', 'Unit created successfully.');
    }

    /**
     * Update the given unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return to_route('units.index')->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the given unit.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::authorize('delete', $unit);

        $unit->delete();

        return to_route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
