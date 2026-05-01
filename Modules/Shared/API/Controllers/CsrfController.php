<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class CsrfController extends Controller
{
    /**
     * Get CSRF token for AJAX requests
     *
     * GET /api/csrf/token
     *
     * @return JsonResponse
     */
    public function getToken(): JsonResponse
    {
        // Get CSRF token from Laravel's session
        $csrfToken = csrf_token();

        // For enhanced security, you can also refresh the token
        if (App::environment('production')) {
            session()->regenerateToken();
            $csrfToken = session()->token();
        }

        $response = response()->json([
            'csrfToken' => $csrfToken
        ]);

        // Add CORS headers explicitly
        $origin = request()->header('Origin');
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:4200',
            'http://localhost:3000',
        ];

        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept');
            $response->headers->set('Access-Control-Expose-Headers', 'X-CSRF-TOKEN');
        }

        return $response;
    }

    /**
     * Alternative method that refreshes the token
     */
    public function refreshToken(): JsonResponse
    {
        session()->regenerateToken();

        $response = response()->json([
            'csrfToken' => session()->token()
        ]);

        $origin = request()->header('Origin');
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:4200',
            'http://localhost:3000',
        ];

        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
