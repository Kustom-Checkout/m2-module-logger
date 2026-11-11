<?php
/**
 * Copyright © Kustom AB
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Test\Unit\Model;

use Klarna\AdminSettings\Model\Configurations\Logger as LoggerConfigurations;
use Klarna\Logger\Model\Handlers\File;
use Klarna\Logger\Model\Logger;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The file logger (var/log/klarna.log) must never contain a recurring token.
 *
 * @coversDefaultClass \Klarna\Logger\Model\Logger
 */
class LoggerRedactionTest extends TestCase
{
    private const SECRET = 'tok_secret_value_123';

    /**
     * @var mixed
     */
    private $captured;

    /**
     * @return array
     */
    public static function messageProvider(): array
    {
        return [
            'json message' => ['{"order_id":"K-1","recurring_token":"' . self::SECRET . '","x":1}', []],
            'json with spaces and escapes' => ['{"recurring_token" : "ab\\"' . self::SECRET . '"}', []],
            'context array' => ['msg', ['response' => ['recurring' => true, 'recurring_token' => self::SECRET]]],
            'context top level' => ['msg', ['recurring_token' => self::SECRET]],
        ];
    }

    /**
     * @covers ::log
     */
    #[DataProvider('messageProvider')]
    public function testTokenNeverReachesHandler(string $message, array $context): void
    {
        $this->newLogger()->log('debug', $message, $context);

        static::assertNotNull($this->captured);
        static::assertStringNotContainsString(self::SECRET, var_export($this->captured, true));
    }

    /**
     * @covers ::log
     */
    public function testNonSensitiveMessageIsUnchanged(): void
    {
        $this->newLogger()->log('debug', '{"order_id":"K-1"}', ['a' => 1]);

        $message = is_array($this->captured) ? $this->captured['message'] : $this->captured->message;
        static::assertSame('{"order_id":"K-1"}', $message);
    }

    /**
     * @covers ::log
     */
    public function testEmptyTokenValuesAreLoggedUnchanged(): void
    {
        $message = '{"recurring_token":null,"b":{"recurring_token":""},"recurring_token" : ""}';
        $this->newLogger()->log('debug', $message, ['recurring_token' => null, 'n' => ['recurring_token' => '']]);

        $captured = is_array($this->captured) ? $this->captured : (array)$this->captured;
        static::assertSame($message, $captured['message']);
        static::assertSame(
            ['recurring_token' => null, 'n' => ['recurring_token' => '']],
            $captured['context']
        );
    }

    /**
     * @covers ::log
     */
    public function testExceptionMessageIsRedacted(): void
    {
        $this->newLogger()->log('error', new \Exception('{"recurring_token":"' . self::SECRET . '"}'));

        static::assertStringNotContainsString(self::SECRET, var_export($this->captured, true));
    }

    private function newLogger(): Logger
    {
        $handler = $this->createMock(File::class);
        $handler->method('handle')->willReturnCallback(function ($record): bool {
            $this->captured = $record;
            return false;
        });
        $config = $this->createMock(LoggerConfigurations::class);
        $config->method('isEnabled')->willReturn(true);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($this->createStub(StoreInterface::class));

        return new Logger($config, $storeManager, [$handler]);
    }
}
