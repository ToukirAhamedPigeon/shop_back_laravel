<?php

namespace Modules\Shared\Infrastructure\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Shared\Application\Services\IOptionsService;
use Modules\Shared\Application\Repositories\IUserLogRepository;
use Modules\Shared\Application\Requests\Common\SelectOptionRequest;
use Modules\Shared\Infrastructure\Helpers\LabelFormatter;
use Illuminate\Support\Facades\Log;

final class OptionsService implements IOptionsService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private IUserLogRepository $userLogRepository
    ) {}

    public function getOptions(string $type, SelectOptionRequest $req): array
    {
        $cacheKey = sprintf(
            'Options:%s:%s:%d:%d',
            strtolower($type),
            $req->search ?? '',
            $req->skip,
            $req->limit
        );

        // if (Cache::has($cacheKey)) {
        //     Log::info('OPTIONS: fetched from REDIS cache', [
        //         'key' => $cacheKey
        //     ]);
        // } else {
        //     Log::info('OPTIONS: fetched from DATABASE (cache miss)', [
        //         'key' => $cacheKey
        //     ]);
        // }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type, $req) {

            $result = match (strtolower($type)) {
                'userlogcollections'  => $this->userLogRepository->getCollections($req),
                'userlogactiontypes'  => $this->userLogRepository->getActionTypes($req),
                'userlogcreators'     => $this->userLogRepository->getCreators($req),
                default               => [],
            };

            // 🔹 Normalize labels (same as DotNet LabelFormatter)
            return array_map(function ($item) {
                return [
                    'value' => (string) $item->value,
                    'label' => LabelFormatter::toReadable((string) $item->label),
                ];
            }, $result);
        });
    }
}
