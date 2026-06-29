<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\System\Services\SystemInfoService;

class SystemInfoController extends Controller
{
    private SystemInfoService $systemInfoService;

    public function __construct(SystemInfoService $systemInfoService)
    {
        $this->systemInfoService = $systemInfoService;
    }

    /**
     * Display the system information panel
     */
    public function index(): View
    {
        $systemInfo = $this->systemInfoService->getAllSystemInfo();

        return view('system::system.info.index', [
            'environment' => $systemInfo['environment'],
            'server' => $systemInfo['server'],
            'extensions' => $systemInfo['php_extensions'],
            'packages' => $systemInfo['composer_packages'],
        ]);
    }

    /**
     * Get system information for AJAX requests
     */
    public function api(): JsonResponse
    {
        return response()->json($this->systemInfoService->getAllSystemInfo());
    }
}
