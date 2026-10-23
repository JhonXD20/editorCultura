<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class EventTime
{
    /** @return array{0: Carbon, 1: bool} */
    public static function resolve(?string $value): array
    {
        $received = now();
        if ($value === null) {
            return [$received, false];
        }
        $occurred = Carbon::parse($value)->setTimezone(config('app.timezone'));
        if ($occurred->lt($received->copy()->subDays(90)) || $occurred->gt($received->copy()->addMinutes(5))) {
            return [$received, false];
        }

        return [$occurred, true];
    }
}
