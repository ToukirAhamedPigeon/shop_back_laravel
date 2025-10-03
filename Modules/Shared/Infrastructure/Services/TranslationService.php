<?php

namespace Modules\Shared\Infrastructure\Services;

use Modules\Shared\Application\Repositories\ITranslationRepository;
use Modules\Shared\Application\Services\ITranslationService;
use Illuminate\Support\Facades\Redis;

class TranslationService implements ITranslationService
{
    protected ITranslationRepository $repo;
    protected int $cacheTtl = 3600; // seconds

    public function __construct(ITranslationRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get translations from cache or DB.
     */
    public function getTranslations(string $lang, ?string $module = null, bool $forceDbFetch = false): array
    {
        $cacheKey = "translations:{$lang}:" . ($module ?? 'common');

        if (!$forceDbFetch) {
            $cached = Redis::get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        }

        $rows = $this->repo->getByLang($lang, $module);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->key->module . '.' . $row->key->key] = $row->value;
        }

        Redis::setex($cacheKey, $this->cacheTtl, json_encode($map));

        return $map;
    }
}
