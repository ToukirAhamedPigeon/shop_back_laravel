<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Response;

class CsrfController extends Controller
{


    /**
     * Optional: return CSRF token in JSON if needed
     */
    public function getToken()
    {
        return response()->json([
            'csrfToken' => csrf_token()
        ]);
    }
}
