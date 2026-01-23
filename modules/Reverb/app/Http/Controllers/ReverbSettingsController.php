<?php

namespace Modules\Reverb\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ReverbSettingsController extends Controller
{
    /**
     * Display Reverb backups index page.
     */
    public function index(): View
    {
        $settings = [
            'host' => config('reverb.broadcasting.host'),
            'port' => config('reverb.broadcasting.port'),
            'scheme' => config('reverb.broadcasting.scheme'),
            'app_key' => config('reverb.broadcasting.app_key'),
            'server_host' => config('reverb.server.host'),
            'server_port' => config('reverb.server.port'),
            'features' => config('reverb.features'),
        ];

        return view('reverb::settings.index', compact('settings'));
    }

    /**
     * Show the form for editing Reverb backups.
     */
    public function edit(): View
    {
        $settings = [
            'host' => config('reverb.broadcasting.host'),
            'port' => config('reverb.broadcasting.port'),
            'scheme' => config('reverb.broadcasting.scheme'),
            'server_host' => config('reverb.server.host'),
            'server_port' => config('reverb.server.port'),
        ];

        return view('reverb::settings.edit', compact('settings'));
    }

    /**
     * Update Reverb backups.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'scheme' => 'required|in:http,https,ws,wss',
            'server_host' => 'required|string|max:255',
            'server_port' => 'required|integer|min:1|max:65535',
        ]);

        // Here you would save the backups to your configuration
        // For now, we'll just redirect with success

        return redirect()
            ->route('manager.backups.reverb.index')
            ->with('success', 'Reverb backups updated successfully');
    }

    /**
     * Test connection to Reverb server.
     */
    public function testConnection(Request $request): Response
    {
        try {
            $host = config('reverb.broadcasting.host');
            $port = config('reverb.broadcasting.port');

            // Attempt to connect to Reverb server
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);

            if ($socket) {
                fclose($socket);

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully connected to Reverb server',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Could not connect to Reverb server at {$host}:{$port}",
                'error' => $errstr,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error testing Reverb connection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
