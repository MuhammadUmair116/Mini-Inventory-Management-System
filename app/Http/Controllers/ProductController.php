<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a paginated, filterable product catalogue.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Product::class);

        $search = trim((string) $request->input('search', ''));
        $categoryId = $request->input('category_id');
        $stockStatus = $request->input('stock_status');
        $isActive = $request->input('is_active');

        $products = Product::query()
            ->with(['category:id,name', 'unit:id,name,short_name'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->when(filled($categoryId), fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($stockStatus === 'low_stock', fn (Builder $query) => $query->lowStock())
            ->when($stockStatus === 'out_of_stock', fn (Builder $query) => $query->outOfStock())
            ->when($stockStatus === 'in_stock', fn (Builder $query) => $query->inStock())
            ->when(
                in_array($isActive, ['1', '0'], true),
                fn (Builder $query) => $query->where('is_active', (bool) $isActive)
            )
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'stock_status' => $stockStatus,
                'is_active' => $isActive,
            ],
            'stats' => [
                'total' => Product::query()->count(),
                'active' => Product::active()->count(),
                'low_stock' => Product::lowStock()->count(),
                'out_of_stock' => Product::outOfStock()->count(),
            ],
            'categoryOptions' => Category::active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $category) => [
                    'value' => $category->id,
                    'label' => $category->name,
                ])->all(),
            'unitOptions' => Unit::active()
                ->orderBy('name')
                ->get(['id', 'name', 'short_name'])
                ->map(fn (Unit $unit) => [
                    'value' => $unit->id,
                    'label' => "{$unit->name} ({$unit->short_name})",
                ])->all(),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return to_route('products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Update the given product.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the given product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        if ($product->purchaseItems()->exists() || $product->saleItems()->exists()) {
            return to_route('products.index')
                ->with('error', 'Product has transaction records and cannot be deleted.');
        }

        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted successfully.');
    }
}
