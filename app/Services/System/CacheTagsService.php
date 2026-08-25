<?php

namespace App\Services\System;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheTagsService
{
    /**
     * Invalidate all cache entries with the given tag.
     *
     * Tagged flush requires a taggable store (Redis/Memcached/Database/Array).
     * On stores without tagging support (e.g. the file driver) Cache::tags()
     * throws BadMethodCallException, which would break the calling business
     * operation (transaction creation/approval). Fall back to a full flush so
     * stale data can never block a write path.
     */
    public function invalidate(string $tag): void
    {
        if (! $this->supportsTags()) {
            // Tagged flush is impossible here; a full flush is the only way
            // to guarantee no stale entries survive the invalidation.
            Cache::store()->flush();
            Log::warning("Cache store does not support tags; performed a FULL flush of the default cache store instead of tag '{$tag}' invalidation. Consider using Redis/Memcached/Database for production.");

            return;
        }

        Cache::tags([$tag])->flush();
    }

    /**
     * Whether the active cache store supports tags.
     */
    public function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }
}
