<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\StorageRedis\Dependency\Client;

use Generated\Shared\Transfer\StorageScanResultTransfer;

interface StorageRedisToStorageRedisClientInterface
{
    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    public function set(string $key, string $value, ?int $ttl = null, ?string $flag = null): bool;

    public function scanKeys(string $pattern, int $limit, int $cursor): StorageScanResultTransfer;
}
