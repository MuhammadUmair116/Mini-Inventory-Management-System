<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a paginated list of categories.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Category::class);

        $search = trim((string) $request->input('search', ''));

        $categories = Category::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('slug', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/Categories/Index', [
            'categories' => $categories,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return to_route('categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Update the given category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the given category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return to_route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
