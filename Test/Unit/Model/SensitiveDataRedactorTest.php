<?php
/**
 * Copyright © Kustom AB
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Test\Unit\Model;

use Klarna\Logger\Model\SensitiveDataRedactor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Klarna\Logger\Model\SensitiveDataRedactor
 */
class SensitiveDataRedactorTest extends TestCase
{
    private const SECRET = 'tok_secret_value_123';

    /**
     * @return array
     */
    public static function arrayProvider(): array
    {
        return [
            'top level' => [
                ['recurring_token' => 'x', 'recurring' => true],
                ['recurring_token' => '[redacted]', 'recurring' => true],
            ],
            'nested' => [
                ['data' => ['deep' => ['recurring_token' => 'x', 'n' => 1]]],
                ['data' => ['deep' => ['recurring_token' => '[redacted]', 'n' => 1]]],
            ],
            'list of arrays' => [
                ['list' => [['recurring_token' => 'x'], ['other' => 'v']]],
                ['list' => [['recurring_token' => '[redacted]'], ['other' => 'v']]],
            ],
            'array valued token' => [
                ['recurring_token' => ['inner' => 'x']],
                ['recurring_token' => '[redacted]'],
            ],
            'null, empty string and empty array unchanged' => [
                ['recurring_token' => null, 'n' => ['recurring_token' => ''], 'e' => ['recurring_token' => []]],
                ['recurring_token' => null, 'n' => ['recurring_token' => ''], 'e' => ['recurring_token' => []]],
            ],
            'non sensitive keys untouched' => [
                ['order_lines' => [['name' => 'a', 'quantity' => 1]], 'locale' => 'en-GB'],
                ['order_lines' => [['name' => 'a', 'quantity' => 1]], 'locale' => 'en-GB'],
            ],
        ];
    }

    /**
     * @dataProvider arrayProvider
     * @covers ::redactArray
     */
    #[DataProvider('arrayProvider')]
    public function testRedactArray(array $input, array $expected): void
    {
        static::assertSame($expected, (new SensitiveDataRedactor())->redactArray($input));
    }

    /**
     * @covers ::redactArray
     */
    public function testRedactArrayCustomKeys(): void
    {
        $redactor = new SensitiveDataRedactor(['api_secret']);

        static::assertSame(
            ['api_secret' => '[redacted]', 'recurring_token' => 't'],
            $redactor->redactArray(['api_secret' => 's', 'recurring_token' => 't'])
        );
    }

    /**
     * @covers ::redactArray
     */
    public function testRedactArrayEmptyKeyListIsNoop(): void
    {
        $data = ['recurring_token' => 't'];

        static::assertSame($data, (new SensitiveDataRedactor([]))->redactArray($data));
    }

    /**
     * @return array
     */
    public static function jsonProvider(): array
    {
        return [
            'string value' => [
                '{"order_id":"K-1","recurring_token":"' . self::SECRET . '","x":1}',
                '{"order_id":"K-1","recurring_token":"[redacted]","x":1}',
            ],
            'spaces and escaped quotes' => [
                '{"recurring_token" : "ab\\"' . self::SECRET . '"}',
                '{"recurring_token" : "[redacted]"}',
            ],
            'null and empty values unchanged' => [
                '{"recurring_token":null,"b":{"recurring_token":""},"recurring_token" : ""}',
                '{"recurring_token":null,"b":{"recurring_token":""},"recurring_token" : ""}',
            ],
            'no token' => ['{"order_id":"K-1"}', '{"order_id":"K-1"}'],
        ];
    }

    /**
     * @dataProvider jsonProvider
     * @covers ::redactJson
     */
    #[DataProvider('jsonProvider')]
    public function testRedactJson(string $input, string $expected): void
    {
        static::assertSame($expected, (new SensitiveDataRedactor())->redactJson($input));
    }

    /**
     * @covers ::redactJson
     */
    public function testRedactJsonCustomKeys(): void
    {
        $redactor = new SensitiveDataRedactor(['api_secret', 'a/b']);

        static::assertSame(
            '{"api_secret":"[redacted]","a/b":"[redacted]","recurring_token":"t"}',
            $redactor->redactJson('{"api_secret":"s","a/b":"z","recurring_token":"t"}')
        );
    }

    /**
     * @covers ::redactJson
     */
    public function testRedactJsonEmptyKeyListIsNoop(): void
    {
        $message = '{"recurring_token":"t"}';

        static::assertSame($message, (new SensitiveDataRedactor([]))->redactJson($message));
    }
}
