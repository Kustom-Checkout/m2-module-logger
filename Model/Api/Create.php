<?php
/**
 * Copyright © Kustom AB
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Model\Api;

use Klarna\Logger\Model\Cleanser;
use Klarna\Logger\Model\LogFactory;
use Klarna\Logger\Model\LogRepository;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Klarna\Logger\Model\Logger;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @internal
 */
class Create
{
    public const REDACTED = '[redacted]';

    /**
     * @var LogFactory
     */
    private $logFactory;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var Cleanser
     */
    private $cleanser;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var string[]
     */
    private array $sensitiveKeys;

    /**
     * @param LogFactory            $logFactory
     * @param LogRepository         $logRepository
     * @param Json                  $json
     * @param Cleanser              $cleanser
     * @param Logger                $logger
     * @param string[]              $sensitiveKeys Keys whose values are redacted at any depth
     * @codeCoverageIgnore
     */
    public function __construct(
        LogFactory $logFactory,
        LogRepository $logRepository,
        Json $json,
        Cleanser $cleanser,
        Logger $logger,
        array $sensitiveKeys = ['recurring_token']
    ) {
        $this->logFactory            = $logFactory;
        $this->logRepository         = $logRepository;
        $this->json                  = $json;
        $this->cleanser              = $cleanser;
        $this->logger                = $logger;
        $this->sensitiveKeys         = $sensitiveKeys;
    }

    /**
     * Adding an entry in the database
     *
     * @param Container $loggerContainer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addEntry(Container $loggerContainer): void
    {
        $request  = $this->anonymizeData($loggerContainer->getRequest());
        $response = $this->anonymizeData($loggerContainer->getResponse());

        if (!isset($response['response_status_code']) && isset($response['code'])) {
            $status = $response['code'];
        } else {
            $status = $response['response_status_code'] ?? Container::DEFAULT_STATUS;
        }

        $log = $this->logFactory->create();
        $log->setStatus($status);
        $log->setAction($loggerContainer->getAction());
        $log->setKlarnaId($loggerContainer->getKlarnaId());
        $log->setIncrementId($loggerContainer->getIncrementId());
        $log->setUrl($loggerContainer->getUrl());
        $log->setMethod($loggerContainer->getMethod());
        $log->setService($loggerContainer->getService());
        $log->setRequest($this->json->serialize($request));
        $log->setResponse($this->json->serialize($response));

        $this->logRepository->save($log);
    }

    /**
     * Anonymize data
     *
     * @param array $data
     * @return array
     */
    private function anonymizeData(array $data): array
    {
        $keys = [
            'billing_address',
            'shipping_address'
        ];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = $this->cleanser->clean($data[$key]);
            }
        }

        return $this->redactSensitiveKeys($data);
    }

    /**
     * Replace the value of every always-redacted key, at any depth; null, '' and [] carry no secret and stay as is
     *
     * @param array $data
     * @return array
     */
    private function redactSensitiveKeys(array $data): array
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
                $data[$key] = $this->redactSensitiveKeys($value);
            }
        }

        return $data;
    }
}
