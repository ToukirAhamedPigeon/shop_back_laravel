<?php

namespace Modules\Shared\Infrastructure\Repositories;

use Modules\Shared\Application\Repositories\ITranslationRepository;
use Modules\Shared\Domain\Entities\TranslationKey as TranslationKeyEntity;
use Modules\Shared\Domain\Entities\TranslationValue as TranslationValueEntity;
use Modules\Shared\Infrastructure\Models\EloquentTranslationKey;
use Modules\Shared\Infrastructure\Models\EloquentTranslationValue;

class EloquentTranslationRepository implements ITranslationRepository
{
    /**
     * Get all translations for a given language, optionally filtered by module.
     *
     * @param string $lang
     * @param string|null $module
     * @return TranslationValueEntity[]
     */
    public function getByLang(string $lang, ?string $module = null): array
    {
        $query = EloquentTranslationValue::with('key')
            ->where('lang', $lang);

        if ($module !== null) {
            $query->whereHas('key', function ($q) use ($module) {
                $q->where('module', $module);
            });
        }

        $values = $query->get();

        return $values->map(function (EloquentTranslationValue $v) {
            return new TranslationValueEntity(
                $v->id,
                $v->key_id,
                $v->lang,
                $v->value,
                $v->created_at ? \DateTimeImmutable::createFromMutable($v->created_at) : null,
                new TranslationKeyEntity(
                    $v->key->id,
                    $v->key->key,
                    $v->key->module,
                     $v->key->created_at ? \DateTimeImmutable::createFromMutable($v->key->created_at) : null
                )
            );
        })->all();
    }

    /**
     * Get a TranslationKey by module and key.
     *
     * @param string $module
     * @param string $key
     * @return TranslationKeyEntity|null
     */
    public function getKey(string $module, string $key): ?TranslationKeyEntity
    {
        $model = EloquentTranslationKey::with('values')
            ->where('module', $module)
            ->where('key', $key)
            ->first();

        if (!$model) return null;

        $values = $model->values->map(function (EloquentTranslationValue $v) {
            return new TranslationValueEntity(
                $v->id,
                $v->key_id,
                $v->lang,
                $v->value,
                $v->created_at ? \DateTimeImmutable::createFromMutable($v->created_at) : null
            );
        })->all();

        return new TranslationKeyEntity(
            $model->id,
            $model->key,
            $model->module,
            $model->created_at,
            $values
        );
    }

    /**
     * Add or update a translation.
     *
     * @param string $module
     * @param string $key
     * @param string $lang
     * @param string $value
     */
    public function addOrUpdate(string $module, string $key, string $lang, string $value): void
    {
        $tkey = EloquentTranslationKey::firstOrCreate(
            ['module' => $module, 'key' => $key],
            ['created_at' => now()]
        );

        $tval = EloquentTranslationValue::firstOrNew(
            ['key_id' => $tkey->id, 'lang' => $lang]
        );
        $tval->value = $value;
        $tval->created_at = $tval->exists ? $tval->created_at : now();
        $tval->save();
    }
}
