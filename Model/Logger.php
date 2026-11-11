<?php
/**
 * Copyright © Kustom AB
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Model;

use DateTimeImmutable;
use Exception;
use LogicException;
use Monolog\LogRecord;
use Monolog\Logger as MonologLogger;
use Psr\Log\LogLevel;
use Psr\Log\AbstractLogger;
use Klarna\Logger\Api\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Klarna\AdminSettings\Model\Configurations\Logger as LoggerConfigurations;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Logging information
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @internal
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    private const REDACTED = '[redacted]';
    private const SENSITIVE_KEYS = ['recurring_token'];

    /**
     * @var LoggerConfigurations
     */
    private LoggerConfigurations $loggerConfigurations;
    /**
     * @var array
     */
    private array $handlers;
    /**
     * @var StoreInterface|null
     */
    private ?StoreInterface $store = null;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param LoggerConfigurations $loggerConfigurations
     * @param StoreManagerInterface $storeManager
     * @param array $handlers
     * @codeCoverageIgnore
     */
    public function __construct(
        LoggerConfigurations $loggerConfigurations,
        StoreManagerInterface $storeManager,
        array $handlers
    ) {
        $this->loggerConfigurations = $loggerConfigurations;
        $this->handlers = $handlers;
        $this->storeManager = $storeManager;
    }

    /**
     * Setting the store
     *
     * @param StoreInterface $store
     */
    public function setStore(StoreInterface $store): void
    {
        $this->store = $store;
    }

    /**
     * Setting the Magento order
     *
     * @param OrderInterface $magentoOrder
     */
    public function setMagentoOrder(OrderInterface $magentoOrder): void
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * @inheritdoc
     */
    public function setRequestContext(RequestInterface $request): void
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param mixed $message
     * @param array $context
     * @throws Exception
     */
    public function log($level, $message, array $context = []): void
    {
        $usedStore = $this->store;
        if ($usedStore === null) {
            $usedStore = $this->storeManager->getStore();
            if ($usedStore === null) {
                return;
            }
        }

        if (!$this->loggerConfigurations->isEnabled($usedStore)) {
            return;
        }

        $handlerInput = $this->getHandlerInput($message, $level, $context);
        foreach ($this->handlers as $handler) {
            if ($handler->handle($handlerInput)) {
                continue;
            }
            return;
        }
    }

    /**
     * Getting the input for the handlers
     *
     * @param mixed $message
     * @param string $level
     * @param array $context
     * @return array
     * @throws Exception
     */
    private function getHandlerInput($message, string $level, array $context)
    {
        $extra = [];
        $channel = 'Klarna';
        $context = $this->redactContext($context);

        if ($message instanceof Exception) {
            $level = LogLevel::CRITICAL;
            $extra = $this->exceptionToArray($message);
            $message = $extra['message'];
        }

        if (is_string($message)) {
            $message = $this->redactString($message);
        }
        if (isset($extra['message']) && is_string($extra['message'])) {
            $extra['message'] = $this->redactString($extra['message']);
        }

        // monolog v3
        if (class_exists(LogRecord::class)) {
            return new LogRecord(
                new DateTimeImmutable(),
                $channel,
                MonologLogger::toMonologLevel($level),
                $message,
                $context,
                $extra
            );
        }

        // monolog v2
        return [
            'message'    => $message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => strtoupper($level),
            'extra'      => $extra,
            'datetime'   => date('Y-m-d H:i:s'),
            'channel'    => $channel
        ];
    }

    /**
     * Convert a exception to an array
     *
     * @param Exception $exception
     * @return array
     */
    private function exceptionToArray(Exception $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString()
        ];
    }

    /**
     * @inheritdoc
     */
    public function logException(\Exception $e, array $context = [])
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * @inheritdoc
     */
    public function logApiRequest(array $request, array $context = [])
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * @inheritdoc
     */
    public function logApiResponse(array $response, array $context = [])
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * @inheritdoc
     */
    public function logArray(array $input, array $context = [])
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * @inheritdoc
     */
    public function forceLogging($message, array $context = [])
    {
        throw new LogicException('This function is deprecated and will be removed in future versions.');
    }

    /**
     * Redacts the values of the sensitive keys (any depth), so they never reach klarna.log; null, '' and [] stay as is
     *
     * @param array $context
     * @return array
     */
    private function redactContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                if ($value !== null && $value !== '' && $value !== []) {
                    $context[$key] = self::REDACTED;
                }
            } elseif (is_array($value)) {
                $context[$key] = $this->redactContext($value);
            }
        }

        return $context;
    }

    /**
     * Redacts sensitive keys inside a JSON string, e.g. a logged request or response body
     *
     * @param string $message
     * @return string
     */
    private function redactString(string $message): string
    {
        $keys = implode('|', array_map('preg_quote', self::SENSITIVE_KEYS));
        $result = preg_replace(
            '/("(?:' . $keys . ')"\\s*:\\s*)"(?:[^"\\\\]|\\\\.)+"/',
            '$1"' . self::REDACTED . '"',
            $message
        );

        return $result ?? $message;
    }
}
