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
        // This is optional but matches .NET's GetAndStoreTokens behavior
        if (App::environment('production')) {
            session()->regenerateToken();
            $csrfToken = session()->token();
        }

        return response()->json([
            'csrfToken' => $csrfToken
        ]);
    }

    /**
     * Alternative method that refreshes the token (matches .NET behavior more closely)
     */
    public function refreshToken(): JsonResponse
    {
        // Regenerate CSRF token (like .NET's GetAndStoreTokens)
        session()->regenerateToken();

        return response()->json([
            'csrfToken' => session()->token()
        ]);
    }
}
