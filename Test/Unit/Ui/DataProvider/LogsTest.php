<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Logger\Test\Unit\Ui\DataProvider;

use Klarna\Logger\Ui\DataProvider\Logs;
use Klarna\Logger\Model\Log;
use Klarna\Logger\Model\ResourceModel\Log\Collection;
use Klarna\Base\Test\Unit\Mock\MockFactory;
use Klarna\Base\Test\Unit\Mock\TestObjectFactory;
use PHPUnit\Framework\TestCase;
use Klarna\Logger\Model\ResourceModel\Log\CollectionFactory;

/**
 * @coversDefaultClass \Klarna\Logger\Ui\DataProvider\Logs
 */
class LogsTest extends TestCase
{
    /**
     * @var MockFactory
     */
    private $mockFactory;
    /**
     * @var Logs
     */
    private $dataProvider;

    /**
     * @covers ::getData()
     */
    public function testGetDataNoItemsExists(): void
    {
        $collectionFactory = $this->mockFactory->create(CollectionFactory::class);
        $collection = $this->mockFactory->create(Collection::class);
        $collection->method('getItems')
            ->willReturn([]);
        $collectionFactory->method('create')->willReturn($collection);

        $this->createModel([
            CollectionFactory::class => $collectionFactory
        ]);

        static::assertEmpty($this->dataProvider->getData());
    }

    /**
     * @covers ::getData()
     */
    public function testGetDataReturnExistingData(): void
    {
        $collectionFactory = $this->mockFactory->create(CollectionFactory::class);
        $collection = $this->mockFactory->create(Collection::class);

        $item = $this->mockFactory->create(Log::class);
        $item->method('getData')
            ->willReturn(
                [
                    'request' => json_encode(['order_amount' => 123]),
                    'response' => json_encode(['order_amount' => 123]),
                ]
            );
        $item->method('getId')
            ->willReturn(1);
        $collection->method('getItems')
            ->willReturn([$item]);
        $collectionFactory->method('create')->willReturn($collection);

        $this->createModel([
            CollectionFactory::class => $collectionFactory
        ]);

        static::assertNotEmpty($this->dataProvider->getData());
    }

    /**
     * @covers ::getData()
     */
    public function testGetDataReturnExistingResult(): void
    {
        $collectionFactory = $this->mockFactory->create(CollectionFactory::class);
        $collection = $this->mockFactory->create(Collection::class);

        $item = $this->mockFactory->create(Log::class);
        $item->method('getData')
            ->willReturn(
                [
                    'request' => json_encode(['order_amount' => 123]),
                    'response' => json_encode(['order_amount' => 123]),
                ]
            );
        $item->method('getId')
            ->willReturn(1);
        $collection->expects(static::once())
            ->method('getItems')
            ->willReturn([$item]);
        $collectionFactory->method('create')->willReturn($collection);

        $this->createModel([
            CollectionFactory::class => $collectionFactory
        ]);

        $resultFirstTry = $this->dataProvider->getData();
        static::assertSame($resultFirstTry, $this->dataProvider->getData());
    }

    protected function setUp(): void
    {
        $this->mockFactory = new MockFactory($this);
    }

    private function createModel(array $instances = []): void
    {
        $objectFactory         = new TestObjectFactory('');
        $this->dataProvider    = $objectFactory->create(Logs::class, [], $instances);
    }
}
