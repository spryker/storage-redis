<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\StorageRedis\Redis;

use Generated\Shared\Transfer\RedisConfigurationTransfer;
use Generated\Shared\Transfer\StorageScanResultTransfer;
use Spryker\Client\StorageRedis\Dependency\Client\StorageRedisToRedisClientInterface;
use Spryker\Client\StorageRedis\Exception\StorageRedisException;
use Spryker\Client\StorageRedis\StorageRedisConfig;

class StorageRedisWrapper implements StorageRedisWrapperInterface
{
    /**
     * @var string
     */
    public const KV_PREFIX = 'kv:';

    /**
     * @var \Spryker\Client\StorageRedis\Dependency\Client\StorageRedisToRedisClientInterface
     */
    protected $redisClient;

    /**
     * @var \Spryker\Client\StorageRedis\StorageRedisConfig
     */
    protected $storageRedisConfig;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var array
     */
    protected $accessStats;

    /**
     * @var string
     */
    protected $connectionKey;

    public function __construct(
        StorageRedisToRedisClientInterface $redisClient,
        StorageRedisConfig $storageRedisConfig
    ) {
        $this->redisClient = $redisClient;
        $this->storageRedisConfig = $storageRedisConfig;
        $this->debug = $this->storageRedisConfig->getDebugMode();
        $this->connectionKey = $this->storageRedisConfig->getRedisConnectionKey();

        $this->resetAccessStats();
        $this->setupConnection($this->storageRedisConfig->getRedisConnectionConfiguration());
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Sets, reads, writes stats array
     */
    public function resetAccessStats(): void
    {
        $this->accessStats = [
            'count' => [
                'read' => 0,
                'write' => 0,
                'delete' => 0,
            ],
            'keys' => [
                'read' => [],
                'write' => [],
                'delete' => [],
            ],
        ];
    }

    public function getAccessStats(): array
    {
        return $this->accessStats;
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        $key = $this->getKeyName($key);
        $value = $this->redisClient->get($this->connectionKey, $key);
        $this->addReadAccessStats($key);

        $result = json_decode((string)$value, true);

        if (json_last_error() === JSON_ERROR_SYNTAX) {
            return $value;
        }

        return $result;
    }

    public function getMulti(array $keys): array
    {
        if (count($keys) === 0) {
            return $keys;
        }

        $transformedKeys = [];
        foreach ($keys as $key) {
            $transformedKeys[] = $this->getKeyName($key);
        }

        $values = array_combine($transformedKeys, $this->redisClient->mget($this->connectionKey, $transformedKeys)) ?: [];
        $this->addMultiReadAccessStats($keys);

        return $values;
    }

    public function getStats(?string $section = null): array
    {
        return $this->redisClient->info($this->connectionKey, $section);
    }

    public function getAllKeys(): array
    {
        return $this->getKeys('*');
    }

    public function getKeys(string $pattern): array
    {
        return $this->redisClient->keys($this->connectionKey, $this->getSearchPattern($pattern));
    }

    public function scanKeys(string $pattern, int $limit, int $cursor): StorageScanResultTransfer
    {
        $result = [];
        $nextCursor = null;
        do {
            [$nextCursor, $keys] = $this->redisScan($this->getSearchPattern($pattern), $nextCursor ?? $cursor);
            $result = $this->concatWithLimit($result, $keys, $limit);
            $hasNextBatch = $nextCursor > 0 && count($result) < $limit;
        } while ($hasNextBatch);

        return (new StorageScanResultTransfer())
            ->setCursor((int)$nextCursor)
            ->setKeys(array_unique($result));
    }

    /**
     * @param array<string> $keys1
     * @param array<string> $keys2
     *
     * @return array<string>
     */
    protected function concatWithLimit(array $keys1, array $keys2, ?int $limit = null): array
    {
        if ($limit === null) {
            return array_merge($keys1, $keys2);
        }

        return array_merge($keys1, array_slice($keys2, 0, $limit - count($keys1)));
    }

    /**
     * @return array [string, string[]]
     */
    protected function redisScan(string $match, int $cursor): array
    {
        return $this->redisClient->scan(
            $this->connectionKey,
            $cursor,
            [
                'COUNT' => $this->storageRedisConfig->getRedisScanChunkSize(),
                'MATCH' => $match,
            ],
        );
    }

    public function getCountItems(): int
    {
        return count($this->redisClient->keys($this->connectionKey, $this->getSearchPattern()));
    }

    public function getDbSize(): int
    {
        return $this->redisClient->dbSize($this->connectionKey);
    }

    /**
     * @param mixed $value
     *
     * @throws \Spryker\Client\StorageRedis\Exception\StorageRedisException
     */
    public function set(string $key, $value, ?int $ttl = null, ?string $flag = null): bool
    {
        $key = $this->getKeyName($key);

        if ($ttl === null) {
            $result = $this->redisClient->set($this->connectionKey, $key, $value, null, null, $flag);
        } else {
            $result = $this->redisClient->setex($this->connectionKey, $key, $ttl, $value);
        }

        $this->addWriteAccessStats($key);
        if (!$result) {
            throw new StorageRedisException(
                sprintf('Could not set redisKey: "%s" with value: "%s"', $key, json_encode($value)),
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $items
     *
     * @throws \Spryker\Client\StorageRedis\Exception\StorageRedisException
     */
    public function setMulti(array $items): void
    {
        $data = [];

        foreach ($items as $key => $value) {
            $dataKey = $this->getKeyName($key);

            if (!is_scalar($value)) {
                $value = json_encode($value);
            }

            $data[$dataKey] = $value;
        }

        if (count($data) === 0) {
            return;
        }

        $result = $this->redisClient->mset($this->connectionKey, $data);
        $this->addMultiWriteAccessStats($data);

        if (!$result) {
            throw new StorageRedisException(
                sprintf(
                    'Could not set redisKeys for items: "[%s]" with values: "[%s]"',
                    implode(',', array_keys($items)),
                    implode(',', array_values($items)),
                ),
            );
        }
    }

    public function delete(string $key): int
    {
        $key = $this->getKeyName($key);
        $result = $this->redisClient->del($this->connectionKey, [$key]);
        $this->addDeleteAccessStats($key);

        return $result;
    }

    public function deleteMulti(array $keys): int
    {
        if (count($keys) === 0) {
            return 0;
        }

        $transformedKeys = [];
        foreach ($keys as $key) {
            $transformedKeys[] = $this->getKeyName($key);
        }

        $result = $this->redisClient->del($this->connectionKey, $transformedKeys);
        $this->addMultiDeleteAccessStats($transformedKeys);

        return $result;
    }

    public function deleteAll(): int
    {
        $dbSizeBefore = $this->getDbSize();
        $this->redisClient->flushDb($this->connectionKey);
        $dbSizeAfter = $this->getDbSize();

        return $dbSizeBefore - $dbSizeAfter;
    }

    /**
     * @param mixed ...$keysOrArgs
     */
    public function evaluate(string $script, int $numKeys, ...$keysOrArgs): bool
    {
        return $this->redisClient->eval($this->connectionKey, $script, $numKeys, ...$keysOrArgs);
    }

    protected function addReadAccessStats(string $key): void
    {
        if ($this->debug) {
            $this->accessStats['count']['read']++;
            $this->accessStats['keys']['read'][] = $key;
        }
    }

    protected function addMultiWriteAccessStats(array $items): void
    {
        if ($this->debug) {
            $this->accessStats['count']['write'] += count($items);
            $this->accessStats['keys']['write'] = $this->accessStats['keys']['write'] + array_keys($items);
        }
    }

    protected function addDeleteAccessStats(string $key): void
    {
        if ($this->debug) {
            $this->accessStats['count']['delete']++;
            $this->accessStats['keys']['delete'][] = $key;
        }
    }

    protected function addMultiDeleteAccessStats(array $keys): void
    {
        if ($this->debug) {
            $this->accessStats['count']['delete'] += count($keys);
            $this->accessStats['keys']['delete'] = $this->accessStats['keys']['delete'] + $keys;
        }
    }

    protected function addMultiReadAccessStats(array $keys): void
    {
        if ($this->debug) {
            $this->accessStats['count']['read'] += count($keys);
            $this->accessStats['keys']['read'] = $this->accessStats['keys']['read'] + $keys;
        }
    }

    protected function addWriteAccessStats(string $key): void
    {
        if ($this->debug) {
            $this->accessStats['count']['write']++;
            $this->accessStats['keys']['write'][] = $key;
        }
    }

    protected function getSearchPattern(string $pattern = '*'): string
    {
        return static::KV_PREFIX . $pattern;
    }

    protected function getKeyName(string $key): string
    {
        return static::KV_PREFIX . $key;
    }

    protected function setupConnection(RedisConfigurationTransfer $configurationTransfer): void
    {
        $this->redisClient->setupConnection(
            $this->connectionKey,
            $configurationTransfer,
        );
    }
}
