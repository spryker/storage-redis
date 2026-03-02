<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\StorageRedis\Redis;

use Generated\Shared\Transfer\StorageScanResultTransfer;

interface StorageRedisWrapperInterface
{
    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @param string|null $flag
     *
     * @throws \Spryker\Client\StorageRedis\Exception\StorageRedisException
     *
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null, ?string $flag = null): bool;

    public function setMulti(array $items): void;

    public function delete(string $key): int;

    public function deleteMulti(array $keys): int;

    public function deleteAll(): int;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    public function getMulti(array $keys): array;

    public function getStats(?string $section = null): array;

    public function getAllKeys(): array;

    public function getKeys(string $pattern): array;

    public function scanKeys(string $pattern, int $limit, int $cursor): StorageScanResultTransfer;

    public function resetAccessStats(): void;

    public function getAccessStats(): array;

    public function getCountItems(): int;

    public function getDbSize(): int;

    public function setDebug(bool $debug): void;

    /**
     * @param string $script
     * @param int $numKeys
     * @param mixed ...$keysOrArgs
     *
     * @return bool
     */
    public function evaluate(string $script, int $numKeys, ...$keysOrArgs): bool;
}
