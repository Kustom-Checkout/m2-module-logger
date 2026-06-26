<?php

/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Test\Integration\Model\Cron;

use Klarna\Base\Test\Integration\Helper\GenericTestCase;
use Klarna\Logger\Cron\UpdateApiLog;
use Magento\Framework\App\ResourceConnection;

/**
 * @internal
 */
class UpdateApiLogTest extends GenericTestCase
{
    /**
     * @var ResourceConnection
     */
    private $connection;
    /**
     * @var UpdateApiLog
     */
    private $updateApiLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->objectManager->get(ResourceConnection::class);
        $this->updateApiLog = $this->objectManager->get(UpdateApiLog::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithEmptyTable(): void
    {
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "`",
            0
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "`",
            0
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithEntriesHavingIncrementId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => '1001']
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            1
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            1
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithEntriesWithoutIncrementId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1'],
            ['klarna_id' => '1', 'increment_id' => '1001']
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1001'",
            2
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE klarna_id = '1' AND increment_id = '1001'",
            2
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithEntriesHavingDifferentIncrementId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => '1001'],
            ['klarna_id' => '2', 'increment_id' => '1002']
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            2
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            2
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithEntriesHavingNullIncrementId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => null]
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            0
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithMixedIncrementIdValues(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => null],
            ['klarna_id' => '1', 'increment_id' => '1002']
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            2
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithNullIncrementIdAndNoMatchingIncrementId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => null]
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NOT NULL",
            0
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithMultipleNullEntries(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => '1001'],
            ['klarna_id' => '1', 'increment_id' => '1002'],
            ['klarna_id' => '1', 'increment_id' => null],
            ['klarna_id' => '1', 'increment_id' => null],
            ['klarna_id' => '1', 'increment_id' => null],
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            3
        );

        // The row with null increment_id is updated to '1001' because it is the first increment_id found for the same klarna_id
        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1001'",
            4
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1002'",
            1
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteMultipleTimes(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => null],
            ['klarna_id' => '1', 'increment_id' => '1001']
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            1
        );

        $this->updateApiLog->execute();
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1001'",
            2
        );

        $this->updateApiLog->execute();
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1001'",
            2
        );

        $this->updateApiLog->execute();
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1001'",
            2
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithMultipleKlarnaIdsAndNullIncrementId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '1', 'increment_id' => null],
            ['klarna_id' => '1', 'increment_id' => '1001'],
            ['klarna_id' => '2', 'increment_id' => null],
            ['klarna_id' => '2', 'increment_id' => '1002']
        ]);

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            2
        );

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id IS NULL",
            0
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1001'",
            2
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE increment_id = '1002'",
            2
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithEmptyOrInvalidKlarnaId(): void
    {
        $this->setupDatabaseEntries([
            ['klarna_id' => '', 'increment_id' => '1001'],
            ['klarna_id' => 'invalid', 'increment_id' => '1002'],
            ['klarna_id' => null, 'increment_id' => '1003']
        ]);

        $this->updateApiLog->execute();

        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE klarna_id = ''",
            1
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE klarna_id = 'invalid'",
            1
        );
        $this->assertDatabaseEntriesCount(
            "SELECT * FROM `" . $this->connection->getTableName('klarna_logs') . "` WHERE klarna_id is null",
            1
        );
    }

    private function setupDatabaseEntries(array $entries): void
    {
        $connection = $this->connection->getConnection();
        foreach ($entries as $entry) {
            $connection->insert('klarna_logs', [
                'klarna_id' => $entry['klarna_id'],
                'increment_id' => $entry['increment_id'] ?? null
            ]);
        }
    }

    private function assertDatabaseEntriesCount(string $query, int $expectedCount): void
    {
        $connection = $this->connection->getConnection();
        $result = $connection->fetchAll($query);
        static::assertCount($expectedCount, $result);
    }
}
