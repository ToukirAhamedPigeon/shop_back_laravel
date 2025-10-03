<?php

namespace Modules\Shared\API\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Services\ITranslationService;

class TranslationsController extends Controller
{
    private ITranslationService $translationService;

    public function __construct(ITranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * GET /translations/get
     */
    public function get(Request $request)
    {
        $lang = $request->query('lang', 'en');
        $module = $request->query('module', null);
        $forceFetch = filter_var($request->query('forceFetch', false), FILTER_VALIDATE_BOOLEAN);

        $translations = $this->translationService->getTranslations($lang, $module, $forceFetch);

        return response()->json([
            'lang' => $lang,
            'module' => $module,
            'translations' => $translations,
        ]);
    }
}
