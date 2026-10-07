<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A tiny file (public/sync.txt) that gets a new random value whenever data changes.
 *
 * Every open page checks it every few seconds and reloads its data when the value
 * differs from the one it loaded with. The web server serves the file directly, so
 * those checks never start PHP.
 */
class SyncStamp
{
    /**
     * The current stamp, creating one if there isn't one yet.
     */
    public static function current(): string
    {
        if (self::read() === '') {
            self::bump();
        }

        return self::read();
    }

    /**
     * Give the stamp a new value so every open page reloads.
     *
     * A failed write is only reported: it should never break the request that changed the data.
     */
    public static function bump(): void
    {
        rescue(fn () => file_put_contents(self::path(), Str::random(16)));
    }

    private static function read(): string
    {
        return is_file(self::path()) ? trim((string) file_get_contents(self::path())) : '';
    }

    private static function path(): string
    {
        return public_path('sync.txt');
    }
}
