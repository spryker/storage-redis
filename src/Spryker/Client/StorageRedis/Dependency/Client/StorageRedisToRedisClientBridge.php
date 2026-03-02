<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\StorageRedis\Dependency\Client;

use Generated\Shared\Transfer\RedisConfigurationTransfer;

class StorageRedisToRedisClientBridge implements StorageRedisToRedisClientInterface
{
    /**
     * @var \Spryker\Client\Redis\RedisClientInterface
     */
    protected $redisClient;

    /**
     * @param \Spryker\Client\Redis\RedisClientInterface $redisClient
     */
    public function __construct($redisClient)
    {
        $this->redisClient = $redisClient;
    }

    public function get(string $connectionKey, string $key): ?string
    {
        return $this->redisClient->get($connectionKey, $key);
    }

    public function setex(string $connectionKey, string $key, int $seconds, string $value): bool
    {
        return $this->redisClient->setex($connectionKey, $key, $seconds, $value);
    }

    public function set(string $connectionKey, string $key, string $value, ?string $expireResolution = null, ?int $expireTTL = null, ?string $flag = null): bool
    {
        return $this->redisClient->set($connectionKey, $key, $value, $expireResolution, $expireTTL, $flag);
    }

    /**
     * @param string $connectionKey
     * @param array<string> $keys
     *
     * @return int
     */
    public function del(string $connectionKey, array $keys): int
    {
        return $this->redisClient->del($connectionKey, $keys);
    }

    /**
     * @param string $connectionKey
     * @param string $script
     * @param int $numKeys
     * @param mixed ...$keysOrArgs
     *
     * @return bool
     */
    public function eval(string $connectionKey, string $script, int $numKeys, ...$keysOrArgs): bool
    {
        return $this->redisClient->eval($connectionKey, $script, $numKeys, ...$keysOrArgs);
    }

    public function connect(string $connectionKey): void
    {
        $this->redisClient->connect($connectionKey);
    }

    public function disconnect(string $connectionKey): void
    {
        $this->redisClient->disconnect($connectionKey);
    }

    public function isConnected(string $connectionKey): bool
    {
        return $this->redisClient->isConnected($connectionKey);
    }

    /**
     * @param string $connectionKey
     * @param array<string> $keys
     *
     * @return array
     */
    public function mget(string $connectionKey, array $keys): array
    {
        return $this->redisClient->mget($connectionKey, $keys);
    }

    public function mset(string $connectionKey, array $dictionary): bool
    {
        return $this->redisClient->mset($connectionKey, $dictionary);
    }

    public function info(string $connectionKey, ?string $section = null): array
    {
        return $this->redisClient->info($connectionKey, $section);
    }

    /**
     * @param string $connectionKey
     * @param string $pattern
     *
     * @return array<string>
     */
    public function keys(string $connectionKey, string $pattern): array
    {
        return $this->redisClient->keys($connectionKey, $pattern);
    }

    /**
     * @param string $connectionKey
     * @param int $cursor
     * @param array<string, mixed> $options
     *
     * @return array [string, string[]]
     */
    public function scan(string $connectionKey, int $cursor, array $options): array
    {
        return $this->redisClient->scan($connectionKey, $cursor, $options);
    }

    public function dbSize(string $connectionKey): int
    {
        return $this->redisClient->dbSize($connectionKey);
    }

    public function flushDb(string $connectionKey): void
    {
        $this->redisClient->flushDb($connectionKey);
    }

    public function setupConnection(string $connectionKey, RedisConfigurationTransfer $configurationTransfer): void
    {
        $this->redisClient->setupConnection($connectionKey, $configurationTransfer);
    }
}
