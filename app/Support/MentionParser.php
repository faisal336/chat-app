<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MentionParser
{
    public const PATTERN = '/(?<![\w@])@([a-zA-Z0-9_.-]{2,32})/';

    public static function extractUsernames(?string $body): array
    {
        if (! $body) {
            return [];
        }

        if (! preg_match_all(self::PATTERN, $body, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }

    public static function resolveUsers(array $usernames): Collection
    {
        if (empty($usernames)) {
            return new Collection;
        }

        return User::active()
            ->whereIn('username', $usernames)
            ->get();
    }

    /**
     * Render mention spans for display. Used in Blade with {!! !!} after escaping.
     * Caller must pass the *already-escaped* body, then this swaps in the markup.
     */
    public static function renderMentions(string $escapedBody): string
    {
        return preg_replace_callback(self::PATTERN, function ($match) {
            $username = $match[1];

            return '<span class="font-medium text-brand-600 dark:text-brand-300 bg-brand-50 dark:bg-brand-500/10 rounded px-1">@'.e($username).'</span>';
        }, $escapedBody);
    }
}
