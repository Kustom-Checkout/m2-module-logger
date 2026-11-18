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
use Klarna\Logger\Model\Cleanser;
use Klarna\Logger\Model\Log;
use Klarna\Logger\Model\LogFactory;
use Klarna\Logger\Model\LogRepository;
use Klarna\Logger\Model\Logger;
use Klarna\Logger\Model\SensitiveDataRedactor;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Klarna\Logger\Model\Api\Create
 */
class CreateRedactionTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $saved = [];

    public function testAddEntryRedactsWithDefaultKeys(): void
    {
        $this->addEntry(
            ['data' => ['recurring_token' => 'secret-token']],
            ['recurring_token' => 'secret-token', 'recurring' => true]
        );

        static::assertSame('{"data":{"recurring_token":"[redacted]"}}', $this->saved['request']);
        static::assertSame('{"recurring_token":"[redacted]","recurring":true}', $this->saved['response']);
    }

    public function testAddEntryAddressMaskingStillApplies(): void
    {
        $this->addEntry(
            ['billing_address' => ['family_name' => 'x', 'country' => 'GB']],
            ['shipping_address' => ['city' => 'y'], 'recurring_token' => 't']
        );

        static::assertSame(
            '{"billing_address":{"family_name":"** REMOVED **","country":"GB"}}',
            $this->saved['request']
        );
        static::assertSame(
            '{"shipping_address":{"city":"** REMOVED **"},"recurring_token":"[redacted]"}',
            $this->saved['response']
        );
    }

    public function testAddEntryUsesConfiguredSensitiveKeys(): void
    {
        $this->addEntry(['api_secret' => 's', 'recurring_token' => 't'], [], ['api_secret']);

        static::assertSame('{"api_secret":"[redacted]","recurring_token":"t"}', $this->saved['request']);
    }

    /**
     * @param array    $request
     * @param array    $response
     * @param string[]|null $sensitiveKeys
     */
    private function addEntry(array $request, array $response, ?array $sensitiveKeys = null): void
    {
        $log = $this->createMock(Log::class);
        $log->method('setRequest')->willReturnCallback(
            function (string $value) use ($log) {
                $this->saved['request'] = $value;
                return $log;
            }
        );
        $log->method('setResponse')->willReturnCallback(
            function (string $value) use ($log) {
                $this->saved['response'] = $value;
                return $log;
            }
        );
        $logFactory = $this->createMock(LogFactory::class);
        $logFactory->method('create')->willReturn($log);

        $json = new Json();
        $args = [
            $logFactory,
            $this->createMock(LogRepository::class),
            $json,
            new Cleanser(),
            $this->createMock(Logger::class),
            $sensitiveKeys === null ? new SensitiveDataRedactor() : new SensitiveDataRedactor($sensitiveKeys)
        ];
        $create = new Create(...$args);

        $container = new Container();
        $container->setRequest($request);
        $container->setResponse($response);
        $create->addEntry($container);
    }
}
