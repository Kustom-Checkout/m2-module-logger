<?php
/**
 * Copyright © Kustom AB
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Model;

/**
 * Redacts the values of always-sensitive keys, for both the klarna_logs table and klarna.log.
 * Extend the key list through the sensitiveKeys argument in di.xml.
 */
class SensitiveDataRedactor
{
    public const REDACTED = '[redacted]';

    /**
     * @var string[]
     */
    private array $sensitiveKeys;

    /**
     * @param string[] $sensitiveKeys Keys whose values are redacted at any depth
     * @codeCoverageIgnore
     */
    public function __construct(array $sensitiveKeys = ['recurring_token'])
    {
        $this->sensitiveKeys = $sensitiveKeys;
    }

    /**
     * Replace the value of every sensitive key, at any depth; null, '' and [] carry no secret and stay as is
     *
     * @param array $data
     * @return array
     */
    public function redactArray(array $data): array
    {
        if ($this->sensitiveKeys === []) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->sensitiveKeys, true)) {
                if ($value !== null && $value !== '' && $value !== []) {
                    $data[$key] = self::REDACTED;
                }
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            }
        }

        return $data;
    }

    /**
     * Redacts sensitive keys inside a JSON string, e.g. a logged request or response body
     *
     * @param string $message
     * @return string
     */
    public function redactJson(string $message): string
    {
        if ($this->sensitiveKeys === []) {
            return $message;
        }

        $keys = implode('|', array_map(
            static fn ($key): string => preg_quote((string)$key, '/'),
            $this->sensitiveKeys
        ));
        $result = preg_replace(
            '/("(?:' . $keys . ')"\\s*:\\s*)"(?:[^"\\\\]|\\\\.)+"/',
            '$1"' . self::REDACTED . '"',
            $message
        );

        return $result ?? $message;
    }
}
