<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\StorageRedis\Dependency\Client;

use Generated\Shared\Transfer\RedisConfigurationTransfer;

interface StorageRedisToRedisClientInterface
{
    public function get(string $connectionKey, string $key): ?string;

    public function setex(string $connectionKey, string $key, int $seconds, string $value): bool;

    public function set(
        string $connectionKey,
        string $key,
        string $value,
        ?string $expireResolution = null,
        ?int $expireTTL = null,
        ?string $flag = null
    ): bool;

    /**
     * @param string $connectionKey
     * @param array<string> $keys
     *
     * @return int
     */
    public function del(string $connectionKey, array $keys): int;

    /**
     * @param string $connectionKey
     * @param string $script
     * @param int $numKeys
     * @param mixed ...$keysOrArgs
     *
     * @return bool
     */
    public function eval(string $connectionKey, string $script, int $numKeys, ...$keysOrArgs): bool;

    public function connect(string $connectionKey): void;

    public function disconnect(string $connectionKey): void;

    public function isConnected(string $connectionKey): bool;

    /**
     * @param string $connectionKey
     * @param array<string> $keys
     *
     * @return array
     */
    public function mget(string $connectionKey, array $keys): array;

    public function mset(string $connectionKey, array $dictionary): bool;

    public function info(string $connectionKey, ?string $section = null): array;

    /**
     * @param string $connectionKey
     * @param string $pattern
     *
     * @return array<string>
     */
    public function keys(string $connectionKey, string $pattern): array;

    /**
     * @param string $connectionKey
     * @param int $cursor
     * @param array<string, mixed> $options
     *
     * @return array [string, string[]]
     */
    public function scan(string $connectionKey, int $cursor, array $options): array;

    public function dbSize(string $connectionKey): int;

    public function flushDb(string $connectionKey): void;

    public function setupConnection(string $connectionKey, RedisConfigurationTransfer $configurationTransfer): void;
}
