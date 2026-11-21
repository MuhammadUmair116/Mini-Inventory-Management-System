<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserManagementService $users) {}

    /**
     * Display a paginated, filterable directory of accounts.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
            'status' => ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->when(filled($filters['role'] ?? null), fn (Builder $query) => $query->where('role', $filters['role']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => (string) ($filters['role'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
            ],
            'stats' => [
                'total' => User::count(),
                'admins' => User::where('role', User::ROLE_ADMIN)->count(),
                'staff' => User::where('role', User::ROLE_STAFF)->count(),
                'inactive' => User::where('status', User::STATUS_INACTIVE)->count(),
            ],
            'roleOptions' => [
                ['value' => User::ROLE_ADMIN, 'label' => 'Admin'],
                ['value' => User::ROLE_STAFF, 'label' => 'Staff'],
            ],
            'statusOptions' => [
                ['value' => User::STATUS_ACTIVE, 'label' => 'Active'],
                ['value' => User::STATUS_INACTIVE, 'label' => 'Inactive'],
            ],
        ]);
    }

    /**
     * Create a new account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return to_route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the given account.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->users->update($user, $actor, $request->validated());

        return to_route('users.index')->with('success', 'User updated successfully.');
    }
}
