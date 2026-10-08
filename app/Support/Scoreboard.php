<?php

namespace App\Support;

use App\Models\Completion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\WeekDay;
use Illuminate\Support\Collection;

/**
 * Each person's points and streak from the completions log, using the points saved on each completion.
 */
class Scoreboard
{
    /**
     * Every person's points this week (Monday to Sunday), points today and current streak.
     *
     * @return Collection<int, array{id: int, name: string, avatar_url: ?string, weekly_points: int, today_points: int, current_streak: int}>
     */
    public static function forEveryone(): Collection
    {
        $today        = today()->toImmutable();
        $weeklyPoints = self::pointsBetween($today->startOfWeek(WeekDay::Monday), $today->endOfWeek(WeekDay::Sunday));
        $todayPoints  = self::pointsBetween($today, $today->endOfDay());
        $doneDays     = Completion::query()
            ->where('status', Completion::DONE)
            ->selectRaw('user_id, date(completed_at) as day')
            ->distinct()
            ->toBase()
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows->pluck('day')->flip());

        return User::all(['id', 'name', 'avatar_style', 'avatar_seed'])->map(fn (User $user) => [
            'id'             => $user->id,
            'name'           => $user->name,
            'avatar_url'     => $user->avatar_url,
            'weekly_points'  => $weeklyPoints[$user->id] ?? 0,
            'today_points'   => $todayPoints[$user->id] ?? 0,
            'current_streak' => self::streak($doneDays[$user->id] ?? collect(), $today),
        ]);
    }

    private static function pointsBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Completion::query()
            ->where('status', Completion::DONE)
            ->whereBetween('completed_at', [$from, $to])
            ->selectRaw('user_id, sum(points) as points')
            ->groupBy('user_id')
            ->pluck('points', 'user_id');
    }

    /**
     * Days in a row with a chore done, counting back from today, or from yesterday if none is done yet today.
     */
    private static function streak(Collection $doneDays, CarbonImmutable $today): int
    {
        $day    = $doneDays->has($today->toDateString()) ? $today : $today->subDay();
        $streak = 0;

        while ($doneDays->has($day->toDateString())) {
            $streak++;
            $day = $day->subDay();
        }

        return $streak;
    }
}
