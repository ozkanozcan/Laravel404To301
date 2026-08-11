<?php

namespace OzkanOzcan\Laravel404To301\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @property int         $id
 * @property string      $url
 * @property string|null $referer
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property int         $hit_count
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 */
class MissingUrl extends Model
{
    protected $fillable = [
        'url',
        'referer',
        'user_agent',
        'ip_address',
        'hit_count',
        'last_seen_at',
    ];

    protected $casts = [
        'hit_count'    => 'integer',
        'last_seen_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('redirect404.tables.missing_urls', 'missing_urls');
    }

    /**
     * Record or increment a hit for a missing URL.
     *
     * Uses upsert: on first occurrence creates the record,
     * on subsequent hits increments hit_count and updates last_seen_at.
     */
    public static function recordHit(string $url, Request $request): void
    {
        $now = now();

        static::upsert(
            [
                [
                    'url'          => $url,
                    'referer'      => substr((string) $request->header('referer', ''), 0, 500) ?: null,
                    'user_agent'   => substr((string) $request->userAgent(), 0, 500) ?: null,
                    'ip_address'   => $request->ip(),
                    'hit_count'    => 1,
                    'last_seen_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ],
            uniqueBy: ['url'],
            update: ['hit_count' => \Illuminate\Support\Facades\DB::raw('hit_count + 1'), 'last_seen_at' => $now, 'updated_at' => $now]
        );
    }
}
