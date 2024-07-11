<?php

namespace Pocket\Framework;

use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str as BaseStr;
use JsonSerializable;

class Str extends BaseStr
{
    /**
     * Check if the content can be morphed to JSON
     *
     * @param mixed $content
     * @return bool
     */
    public static function shouldBeJson(mixed $content): bool
    {
        return $content instanceof Arrayable ||
            $content instanceof Jsonable ||
            $content instanceof ArrayObject ||
            $content instanceof JsonSerializable ||
            is_array($content);
    }

    /**
     * Morph content to JSON
     *
     * @param mixed $content
     * @return string|false
     */
    public static function morphToJson(mixed $content): false|string
    {
        if ($content instanceof Jsonable) {
            return $content->toJson();
        } elseif ($content instanceof Arrayable) {
            return json_encode($content->toArray());
        }

        return json_encode($content);
    }

    /**
     * Check if the value is JSON string
     *
     * @param mixed $value
     * @return bool
     */
    public static function isJsonString(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if the value is a regular expression
     *
     * @param mixed $value
     * @return bool
     */
    public static function isRegExp(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        set_error_handler(function () {
        }, E_WARNING);
        $isRegExp = preg_match($value, '') !== false;
        restore_error_handler();

        return $isRegExp;
    }
}