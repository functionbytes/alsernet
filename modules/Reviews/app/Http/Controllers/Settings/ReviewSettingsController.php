<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Reviews\Models\ReviewGoogleLocation;

class ReviewSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.settings.manage');
    }

    public function index(): View
    {
        $settings = [
            'sync_interval_minutes' => config('reviews.general.sync_interval_minutes'),
            'auto_publish_replies' => config('reviews.general.auto_publish_replies'),
            'default_moderation_visible' => config('reviews.general.default_moderation_visible'),
            'rate_limit_per_minute' => config('reviews.general.rate_limit_per_minute'),
        ];

        return view('reviews::settings.config', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sync_interval_minutes' => 'required|integer|min:5|max:1440',
            'auto_publish_replies' => 'required|string|in:disabled,positive,negative,all',
            'default_moderation_visible' => 'required|boolean',
            'rate_limit_per_minute' => 'required|integer|min:10|max:100',
            'google_client_id' => 'nullable|string',
            'google_client_secret' => 'nullable|string',
        ]);

        // Save general settings
        foreach (['sync_interval_minutes', 'auto_publish_replies', 'default_moderation_visible', 'rate_limit_per_minute'] as $key) {
            if (isset($validated[$key])) {
                $value = is_bool($validated[$key]) ? (int) $validated[$key] : $validated[$key];
                Setting::set("reviews.{$key}", $value);
            }
        }

        // Update Google credentials in .env file
        if ($request->filled('google_client_id') && ! str_contains($request->google_client_id, '•')) {
            $this->updateEnvVariable('GOOGLE_CLIENT_ID', $validated['google_client_id']);
        }

        if ($request->filled('google_client_secret') && ! str_contains($request->google_client_secret, '•')) {
            $this->updateEnvVariable('GOOGLE_CLIENT_SECRET', $validated['google_client_secret']);
        }

        activity()
            ->causedBy(auth()->user())
            ->log('Review settings updated');

        return redirect()
            ->route('settings.reviews.config.index')
            ->with('success', 'Configuración actualizada correctamente');
    }

    public function widget(): View
    {
        $locations = ReviewGoogleLocation::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reviews::settings.widget', compact('locations'));
    }

    /**
     * Update or add a variable in the .env file
     */
    protected function updateEnvVariable(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);
        $value = str_replace('"', '\\"', $value);
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}=\"{$value}\"";

        if (preg_match($pattern, $envContent)) {
            // Update existing variable
            $envContent = preg_replace($pattern, $replacement, $envContent);
        } else {
            // Add new variable at the end
            $envContent .= "\n{$replacement}\n";
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache to reload new values
        \Artisan::call('config:clear');
    }
}
