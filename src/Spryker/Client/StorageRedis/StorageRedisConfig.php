<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\StorageRedis;

use Generated\Shared\Transfer\RedisConfigurationTransfer;
use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Client\Kernel\AbstractBundleConfig;
use Spryker\Shared\StorageRedis\StorageRedisConstants;

class StorageRedisConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    protected const STORAGE_REDIS_CONNECTION_KEY = 'STORAGE_REDIS';

    /**
     * @var int
     */
    protected const REDIS_DEFAULT_DATABASE = 0;

    /**
     * @var int
     */
    protected const SCAN_CHUNK_SIZE = 100;

    /**
     * @api
     *
     * @return bool
     */
    public function getDebugMode(): bool
    {
        return $this->get(StorageRedisConstants::STORAGE_REDIS_DEBUG_MODE, false);
    }

    /**
     * @api
     *
     * @return \Generated\Shared\Transfer\RedisConfigurationTransfer
     */
    public function getRedisConnectionConfiguration(): RedisConfigurationTransfer
    {
        return (new RedisConfigurationTransfer())
            ->setDataSourceNames(
                $this->getDataSourceNames(),
            )
            ->setConnectionCredentials(
                $this->getConnectionCredentials(),
            )
            ->setClientOptions(
                $this->getConnectionOptions(),
            );
    }

    /**
     * @api
     *
     * @return string
     */
    public function getRedisConnectionKey(): string
    {
        return static::STORAGE_REDIS_CONNECTION_KEY;
    }

    /**
     * @api
     *
     * @return int
     */
    public function getRedisScanChunkSize(): int
    {
        return static::SCAN_CHUNK_SIZE;
    }

    /**
     * @return array<string>
     */
    protected function getDataSourceNames(): array
    {
        return $this->get(StorageRedisConstants::STORAGE_REDIS_DATA_SOURCE_NAMES, []);
    }

    /**
     * @return \Generated\Shared\Transfer\RedisCredentialsTransfer
     */
    protected function getConnectionCredentials(): RedisCredentialsTransfer
    {
        return (new RedisCredentialsTransfer())
            ->setScheme($this->getScheme())
            ->setHost($this->get(StorageRedisConstants::STORAGE_REDIS_HOST))
            ->setPort($this->get(StorageRedisConstants::STORAGE_REDIS_PORT))
            ->setDatabase($this->get(StorageRedisConstants::STORAGE_REDIS_DATABASE, static::REDIS_DEFAULT_DATABASE))
            ->setPassword($this->get(StorageRedisConstants::STORAGE_REDIS_PASSWORD, false))
            ->setIsPersistent($this->get(StorageRedisConstants::STORAGE_REDIS_PERSISTENT_CONNECTION, false));
    }

    /**
     * @deprecated Use $this->get(StorageRedisConstants::STORAGE_REDIS_SCHEME) instead. Added for BC reason only.
     *
     * @return string
     */
    protected function getScheme(): string
    {
        return $this->get(StorageRedisConstants::STORAGE_REDIS_SCHEME, false) ?:
            $this->get(StorageRedisConstants::STORAGE_REDIS_PROTOCOL);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConnectionOptions(): array
    {
        return $this->get(StorageRedisConstants::STORAGE_REDIS_CONNECTION_OPTIONS, []);
    }
}
