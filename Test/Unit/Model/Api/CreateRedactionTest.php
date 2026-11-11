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

    public function testAddEntryRedactsTopLevelRecurringToken(): void
    {
        $this->addEntry(
            ['a' => 'b'],
            ['recurring_token' => 'secret-token', 'recurring' => true]
        );

        static::assertSame(
            '{"recurring_token":"[redacted]","recurring":true}',
            $this->saved['response']
        );
        static::assertSame('{"a":"b"}', $this->saved['request']);
    }

    public function testAddEntryRedactsNestedRecurringToken(): void
    {
        $this->addEntry(
            ['data' => ['deep' => ['recurring_token' => 'secret-token', 'x' => 1]]],
            ['list' => [['recurring_token' => 'secret-token'], ['other' => 'v']]]
        );

        static::assertSame('{"data":{"deep":{"recurring_token":"[redacted]","x":1}}}', $this->saved['request']);
        static::assertSame('{"list":[{"recurring_token":"[redacted]"},{"other":"v"}]}', $this->saved['response']);
        static::assertStringNotContainsString('secret-token', $this->saved['request'] . $this->saved['response']);
    }

    public function testAddEntryRedactsArrayValuedToken(): void
    {
        $this->addEntry([], ['recurring_token' => ['inner' => 'secret-token']]);

        static::assertSame('{"recurring_token":"[redacted]"}', $this->saved['response']);
    }

    public function testAddEntryEmptyTokenValuesAreLoggedUnchanged(): void
    {
        $this->addEntry(
            ['recurring_token' => null, 'n' => ['recurring_token' => '']],
            ['recurring_token' => [], 'recurring' => false]
        );

        static::assertSame('{"recurring_token":null,"n":{"recurring_token":""}}', $this->saved['request']);
        static::assertSame('{"recurring_token":[],"recurring":false}', $this->saved['response']);
    }

    public function testAddEntryWithoutTokenIsUnchanged(): void
    {
        $this->addEntry(
            ['order_lines' => [['name' => 'a', 'quantity' => 1]], 'locale' => 'en-GB'],
            ['response_status_code' => 200, 'order_id' => 'abc']
        );

        static::assertSame(
            '{"order_lines":[{"name":"a","quantity":1}],"locale":"en-GB"}',
            $this->saved['request']
        );
        static::assertSame('{"response_status_code":200,"order_id":"abc"}', $this->saved['response']);
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
        $this->addEntry(
            ['api_secret' => 's', 'recurring_token' => 't'],
            [],
            ['api_secret']
        );

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
            $this->createMock(Logger::class)
        ];
        if ($sensitiveKeys !== null) {
            $args[] = $sensitiveKeys;
        }
        $create = new Create(...$args);

        $container = new Container();
        $container->setRequest($request);
        $container->setResponse($response);
        $create->addEntry($container);
    }
}
