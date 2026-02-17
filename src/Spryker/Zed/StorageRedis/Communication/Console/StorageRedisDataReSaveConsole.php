<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\StorageRedis\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\StoreAwareConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\StorageRedis\Communication\StorageRedisCommunicationFactory getFactory()
 */
class StorageRedisDataReSaveConsole extends StoreAwareConsole
{
    /**
     * @var string
     */
    protected const COMMAND_NAME = 'storage:redis:re-save';

    /**
     * @uses \Spryker\Client\StorageRedis\Redis\StorageRedisWrapper::KV_PREFIX
     *
     * @var string
     */
    protected const KV_PREFIX = 'kv:';

    /**
     * @var int
     */
    protected const BULK_SIZE = 100;

    /**
     * @var int
     */
    protected const DEFAULT_SLEEP_TIME = 10;

    /**
     * @var float
     */
    protected const DEFAULT_MAX_DURATION = 0.05;

    /**
     * @var string
     */
    protected const OPTION_CURSOR = 'cursor';

    /**
     * @var string
     */
    protected const OPTION_CURSOR_SHORT = 'c';

    /**
     * @var string
     */
    protected const OPTION_PATTERN = 'pattern';

    /**
     * @var string
     */
    protected const OPTION_PATTERN_SHORT = 'p';

    /**
     * @var string
     */
    protected const OPTION_TTL = 'ttl';

    /**
     * @var string
     */
    protected const OPTION_TTL_SHORT = 't';

    /**
     * @var string
     */
    protected const OPTION_DRY = 'dry';

    /**
     * @var string
     */
    protected const OPTION_DRY_SHORT = 'd';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Re-saves storage data with current settings. Uses bulk operation with adaptive timeout between iterations. Can be used in production mode.')
            ->addOption(static::OPTION_CURSOR, static::OPTION_CURSOR_SHORT, InputOption::VALUE_OPTIONAL, 'Defines a cursor for scanning keys in sotrage.', 0)
            ->addOption(static::OPTION_PATTERN, static::OPTION_PATTERN_SHORT, InputOption::VALUE_OPTIONAL, 'Pattern to scan keys (applied as suffix to KV prefix). Use glob-style patterns, e.g. "product:*". Default: "*"', '*')
            ->addOption(static::OPTION_TTL, static::OPTION_TTL_SHORT, InputOption::VALUE_OPTIONAL, 'TTL in seconds to set for each key. If omitted, existing TTL is preserved (KEEPTTL). Warning: if provided, this TTL will be applied to all matching keys; use this option with a great care.')
            ->addOption(static::OPTION_DRY, static::OPTION_DRY_SHORT, InputOption::VALUE_NONE, 'Dry run: scan and count matching keys without resaving or setting TTL.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('max_execution_time', 0);
        $cursor = (int)$input->getOption(static::OPTION_CURSOR);

        gc_enable();
        $sleepMs = static::DEFAULT_SLEEP_TIME;
        $maxDuration = static::DEFAULT_MAX_DURATION;
        $redis = $this->getFactory()->getStorageRedisClient();
        $countItems = 0;

        // Resolve scan pattern and TTL
        $inputPattern = (string)$input->getOption(static::OPTION_PATTERN);
        $scanPattern = '*';
        if ($inputPattern !== '' && $inputPattern !== '*') {
            $scanPattern = $inputPattern;
        }

        $ttlOption = $input->getOption(static::OPTION_TTL);
        $useTtl = $ttlOption !== null && $ttlOption !== '';
        $ttlValue = $useTtl ? (int)$ttlOption : null;

        $dryMode = (bool)$input->getOption(static::OPTION_DRY);
        $skippedItems = 0;

        // Collect up to 10 sample keys in dry mode to display after the run
        $sampleKeys = [];

        do {
            $start = microtime(true);

            $storageScanResultTransfer = $redis->scanKeys($scanPattern, static::BULK_SIZE, $cursor);
            $cursor = $storageScanResultTransfer->getCursor();
            $countItems += count($storageScanResultTransfer->getKeys());
            foreach ($storageScanResultTransfer->getKeys() as $key) {
                $key = ltrim($key, 'kv:');
                if ($dryMode) {
                    // Collect up to first 10 keys for inspection in dry mode
                    if (count($sampleKeys) < 10) {
                        $sampleKeys[] = $key;
                    }

                    // In dry mode do not perform any Redis get/set to avoid modifying data; just count keys
                    $skippedItems++;

                    continue;
                }
                $value = $redis->get($key);
                if (!$value) {
                    continue;
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                // If TTL provided, set new TTL; otherwise keep existing TTL via KEEPTTL
                if ($useTtl) {
                    $redis->set($key, $value, $ttlValue);
                } else {
                    $redis->set($key, $value, null, 'KEEPTTL');
                }
            }
            unset($storageScanResultTransfer);

            $duration = microtime(true) - $start;

            if ($duration > $maxDuration) {
                $sleepMs = min($sleepMs + static::DEFAULT_SLEEP_TIME, 200);
            } elseif ($sleepMs > static::DEFAULT_SLEEP_TIME) {
                $sleepMs = max($sleepMs - 5, static::DEFAULT_SLEEP_TIME);
            }
            echo sprintf("\rProgress: %s items. Current cursor: %s", $countItems, $cursor);

            gc_collect_cycles();
            usleep($sleepMs * 1000);
        } while ($cursor !== 0);

        echo "\n";

        if ($dryMode) {
            echo sprintf("Dry run complete: scanned %d keys (skipped %d get/set operations). No data was modified.\n", $countItems, $skippedItems);

            if ($sampleKeys) {
                echo 'Sample keys (first ' . count($sampleKeys) . "):\n";
                foreach ($sampleKeys as $sampleKey) {
                    echo '- ' . $sampleKey . "\n";
                }
            }
        }

        return static::CODE_SUCCESS;
    }
}
