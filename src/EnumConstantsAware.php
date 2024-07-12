<?php

namespace Procket\Framework;

use Illuminate\Support\Collection;
use ReflectionClass;

/**
 * Class enumerable constants aware trait
 */
trait EnumConstantsAware
{
    /**
     * Enumerable constant information of the current class
     * @var Collection
     */
    private static Collection $enumerable;

    /**
     * Get the enumerable constant information of the current class
     *
     * ```
     * The returned Collection format:
     * [
     *      'constant name' => [
     *          'value' => 'constant value'
     *          'is_private' => false,
     *          'is_protected' => false,
     *          'is_public' => true,
     *          'doc_info' => [
     *              'summary' => 'Brief description',
     *              'description' => 'Detailed Description',
     *              'tags' => [...]
     *          ]
     *      ],
     *      ...
     * ]
     * ```
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerable(string $nameFilter = null): Collection
    {
        if (isset(self::$enumerable)) {
            return collect(self::$enumerable);
        }

        $rfClass = new ReflectionClass(static::class);
        $rfConstants = $rfClass->getReflectionConstants();

        $constants = [];
        foreach ($rfConstants as $rfConstant) {
            $constants[$rfConstant->getName()] = [
                'value' => $rfConstant->getValue(),
                'is_private' => $rfConstant->isPrivate(),
                'is_protected' => $rfConstant->isProtected(),
                'is_public' => $rfConstant->isPublic(),
                'doc_info' => PhpDoc::parseDocInfo((string)$rfConstant->getDocComment())
            ];
        }
        self::$enumerable = collect($constants);

        if ($nameFilter) {
            return self::$enumerable->filter(function ($item, $name) use ($nameFilter) {
                if (Str::isRegExp($nameFilter)) {
                    return (bool)preg_match($nameFilter, $name);
                } else {
                    return Str::contains($name, $nameFilter);
                }
            });
        }

        return collect(self::$enumerable);
    }

    /**
     * Get constant name => constant value
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerableNameValueMap(string $nameFilter = null): Collection
    {
        return static::enumerable($nameFilter)->transform(function ($info) {
            return $info['value'];
        });
    }

    /**
     * Get constant value => constant name
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerableValueNameMap(string $nameFilter = null): Collection
    {
        return static::enumerableNameValueMap($nameFilter)->flip();
    }

    /**
     * Get constant name => constant brief description
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerableNameSummaryMap(string $nameFilter = null): Collection
    {
        return static::enumerable($nameFilter)->transform(function ($info) {
            return $info['doc_info']['summary'];
        });
    }

    /**
     * Get constant value => constant brief description
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerableValueSummaryMap(string $nameFilter = null): Collection
    {
        $map = collect();

        static::enumerable($nameFilter)->each(function ($info) use ($map) {
            if (is_array($info['value'])) {
                $map->put(json_encode($info['value']), $info['doc_info']['summary']);
            } else {
                $map->put($info['value'], $info['doc_info']['summary']);
            }
        });

        return $map;
    }

    /**
     * Get the enumerable constant names of the current class
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerableNames(string $nameFilter = null): Collection
    {
        return static::enumerableNameValueMap($nameFilter)->keys();
    }

    /**
     * Get the enumerable constant values of the current class
     *
     * @param string|null $nameFilter Constant name filter, can be a regular expression
     * @return Collection
     */
    public static function enumerableValues(string $nameFilter = null): Collection
    {
        return static::enumerable($nameFilter)->transform(function ($info) {
            return $info['value'];
        })->values();
    }
}