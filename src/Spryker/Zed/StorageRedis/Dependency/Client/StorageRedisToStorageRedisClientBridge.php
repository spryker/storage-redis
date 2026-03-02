<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\StorageRedis\Dependency\Client;

use Generated\Shared\Transfer\StorageScanResultTransfer;
use Spryker\Client\StorageRedis\StorageRedisClientInterface;

class StorageRedisToStorageRedisClientBridge implements StorageRedisToStorageRedisClientInterface
{
    /**
     * @var \Spryker\Client\StorageRedis\StorageRedisClientInterface
     */
    protected StorageRedisClientInterface $storageRedisClient;

    /**
     * @param \Spryker\Client\StorageRedis\StorageRedisClientInterface $storageRedisClient
     */
    public function __construct($storageRedisClient)
    {
        $this->storageRedisClient = $storageRedisClient;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->storageRedisClient->get($key);
    }

    public function set(string $key, string $value, ?int $ttl = null, ?string $flag = null): bool
    {
        return $this->storageRedisClient->set($key, $value, $ttl, $flag);
    }

    public function scanKeys(string $pattern, int $limit, int $cursor): StorageScanResultTransfer
    {
        return $this->storageRedisClient->scanKeys($pattern, $limit, $cursor);
    }
}
