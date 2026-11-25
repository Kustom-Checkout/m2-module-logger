<?php

/**
 * Copyright © Kustom AB
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

declare(strict_types=1);

namespace Klarna\Logger\Test\Unit\Model\Api;

use Klarna\Logger\Model\Api\Container;
use Klarna\Logger\Model\Api\Create;
use Klarna\Logger\Model\ResourceModel\Log\CollectionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Klarna\Logger\Model\Api\Create
 */
class CreateTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @var Container|null
     */
    private ?Container $container = null;

    /**
     * @var CollectionFactory|null
     */
    private ?CollectionFactory $collectionFactory = null;

    /**
     * @var Create|null
     */
    private ?Create $createLogger = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->container = $this->objectManager->get(Container::class);
        $this->collectionFactory = $this->objectManager->create(CollectionFactory::class);
        $this->createLogger = $this->objectManager->create(Create::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testAddEntryDataIsAnonymized(): void
    {
        $request = [
            'billing_address' => [
                'family_name' => 'my last name',
            ],
            'shipping_address' => [
                'family_name' => 'my last name',
            ],
        ];
        $response = array_merge(['response_status_code' => 1], $request);

        $expectedData = [
            'request' => json_encode([
                'billing_address' => [
                    'family_name' => '** REMOVED **',
                ],
                'shipping_address' => [
                    'family_name' => '** REMOVED **',
                ],
            ]),
            'response' => json_encode([
                'response_status_code' => 1,
                'billing_address' => [
                    'family_name' => '** REMOVED **',
                ],
                'shipping_address' => [
                    'family_name' => '** REMOVED **',
                ],
            ])
        ];

        $this->container->setRequest($request);
        $this->container->setResponse($response);
        $this->createLogger->addEntry($this->container);

        $collection = $this->collectionFactory->create()
            ->setPageSize(1)
            ->setOrder('log_id');
        $log = $collection->getFirstItem();

        $this->assertEquals($expectedData, $log->toArray(['request', 'response']));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testAddEntryRecurringTokenIsRedactedAtAnyDepth(): void
    {
        $request = ['nested' => ['recurring_token' => 'secret-token']];
        $response = [
            'response_status_code' => 200,
            'recurring_token' => 'secret-token',
            'recurring' => true,
        ];

        $this->container->setRequest($request);
        $this->container->setResponse($response);
        $this->createLogger->addEntry($this->container);

        $log = $this->collectionFactory->create()
            ->setPageSize(1)
            ->setOrder('log_id')
            ->getFirstItem();
        $data = $log->toArray(['request', 'response']);

        $this->assertEquals(
            json_encode(['nested' => ['recurring_token' => '[redacted]']]),
            $data['request']
        );
        $this->assertEquals(
            json_encode(['response_status_code' => 200, 'recurring_token' => '[redacted]', 'recurring' => true]),
            $data['response']
        );
    }
}
