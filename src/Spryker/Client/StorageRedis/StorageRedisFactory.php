<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\StorageRedis;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\StorageRedis\Dependency\Client\StorageRedisToRedisClientInterface;
use Spryker\Client\StorageRedis\Redis\StorageRedisWrapper;
use Spryker\Client\StorageRedis\Redis\StorageRedisWrapperInterface;

/**
 * @method \Spryker\Client\StorageRedis\StorageRedisConfig getConfig()
 */
class StorageRedisFactory extends AbstractFactory
{
    public function createStorageRedisWrapper(): StorageRedisWrapperInterface
    {
        return new StorageRedisWrapper(
            $this->getRedisClient(),
            $this->getConfig(),
        );
    }

    public function getRedisClient(): StorageRedisToRedisClientInterface
    {
        return $this->getProvidedDependency(StorageRedisDependencyProvider::CLIENT_REDIS);
    }
}
