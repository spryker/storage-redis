<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\StorageRedis;

use Spryker\Shared\StorageRedis\StorageRedisConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class StorageRedisConfig extends AbstractBundleConfig
{
    /**
     * @var int
     */
    public const DEFAULT_REDIS_DATABASE = 0;

    /**
     * @var string
     */
    protected const DEFAULT_RDB_DUMP_PATH = '/var/lib/redis/dump.rdb';

    /**
     * @var int
     */
    protected const DEFAULT_STORAGE_REDIS_PORT = 6379;

    /**
     * @var string
     */
    protected const DEFAULT_STORAGE_REDIS_HOST = '127.0.0.1';

    /**
     * @api
     *
     * @return int
     */
    public function getRedisPort(): int
    {
        return $this->get(StorageRedisConstants::STORAGE_REDIS_PORT, static::DEFAULT_STORAGE_REDIS_PORT);
    }

    /**
     * @api
     *
     * @return string
     */
    public function getRdbDumpPath(): string
    {
        return $this->get(StorageRedisConstants::RDB_DUMP_PATH, static::DEFAULT_RDB_DUMP_PATH);
    }

    /**
     * @api
     *
     * @return string
     */
    public function getRedisHost(): string
    {
        return $this->get(
            StorageRedisConstants::STORAGE_REDIS_HOST,
            static::DEFAULT_STORAGE_REDIS_HOST,
        );
    }
}
