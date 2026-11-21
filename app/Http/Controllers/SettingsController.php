<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Display the editable application settings.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Setting::class);

        return Inertia::render('Settings/Index', [
            'settings' => $this->settings->all(),
            'options' => $this->settings->options(),
        ]);
    }

    /**
     * Persist the application settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('update', Setting::class);

        $this->settings->update($request->validated());

        return to_route('settings.index')->with('success', 'Settings updated successfully.');
    }
}
