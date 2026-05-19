<?php

namespace App\Traits;

trait Shardable
{
    /**
     * Get the connection name for the shard.
     *
     * @return string
     */
    public function getShardConnection(): string
    {
        $shardId = $this->id % 4; // 4 shards
        return "pgsql_shard_{$shardId}";
    }
}
