<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a plain `Y-m-d` date and reads it back as the start of that day in the app timezone.
 *
 * @implements CastsAttributes<CarbonImmutable|null, \DateTimeInterface|string|null>
 */
class DateOnly implements CastsAttributes, SerializesCastableAttributes
{
    public bool $withoutObjectCaching = true;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::parse($value, config('app.timezone'))->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return CarbonImmutable::parse($value)->format('Y-m-d');
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // Eloquent has already turned $value into a UTC ISO string here, so format from the stored date instead.
        return $this->get($model, $key, $attributes[$key], $attributes)?->format('Y-m-d');
    }
}
