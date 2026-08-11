<?php

namespace OzkanOzcan\Laravel404To301\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string      $from_url
 * @property string      $to_url
 * @property int         $redirect_code
 * @property int         $hits
 * @property bool        $is_active
 * @property string|null $note
 */
class Redirect extends Model
{
    protected $fillable = [
        'from_url',
        'to_url',
        'redirect_code',
        'hits',
        'is_active',
        'note',
    ];

    protected $casts = [
        'redirect_code' => 'integer',
        'hits'          => 'integer',
        'is_active'     => 'boolean',
    ];

    public function getTable(): string
    {
        return config('redirect404.tables.redirects', 'redirects');
    }

    /**
     * Find an active redirect rule by its source path.
     *
     * @param  string  $url  The incoming request path (e.g. /old-page)
     */
    public static function findByFromUrl(string $url): ?self
    {
        return static::where('from_url', $url)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Increment the hit counter for this redirect.
     */
    public function incrementHits(): void
    {
        $this->increment('hits');
    }
}
