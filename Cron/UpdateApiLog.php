<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Cron;

use Klarna\Logger\Model\Logger;
use Klarna\Logger\Model\LogRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Klarna\Logger\Model\ResourceModel\Log\CollectionFactory as LogCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * @api
 */
class UpdateApiLog
{
    /**
     * @var LogRepository
     */
    private LogRepository $logRepository;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var LogCollectionFactory
     */
    private LogCollectionFactory $logCollectionFactory;

    /**
     * @param LogRepository         $logRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Logger                $logger
     * @param LogCollectionFactory  $logCollectionFactory
     * @codeCoverageIgnore
     */
    public function __construct(
        LogRepository         $logRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger                $logger,
        LogCollectionFactory  $logCollectionFactory
    ) {
        $this->logRepository         = $logRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger                = $logger;
        $this->logCollectionFactory  = $logCollectionFactory;
    }

    /**
     * Updating the increment_id field in the Klarna logs table
     *
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $incrementIds = $this->getIncrementIds();
        $this->updateIncrementId($incrementIds);
    }

    /**
     * Getting back the increment ids
     *
     * @return array
     */
    private function getIncrementIds(): array
    {
        $logs = $this->logCollectionFactory->create();
        $logs->addFieldToFilter('increment_id', ['neq' => null]);
        $logs->addFieldToFilter('klarna_id', ['neq' => null]);
        $logs->addFieldToSelect(['klarna_id', 'increment_id']);
        $logs->getSelect()->group('klarna_id');

        $result = [];
        foreach ($logs->getItems() as $item) {
            $result[$item->getKlarnaId()] = $item->getIncrementId();
        }

        return $result;
    }

    /**
     * Updating the increment id column for the rows which don't have an increment id value
     *
     * @param array $incrementIds
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function updateIncrementId(array $incrementIds): void
    {
        $logs = $this->logCollectionFactory->create();
        $logs->addFieldToFilter('increment_id', ['null' => true]);
        $logs->addFieldToSelect(['klarna_id', 'increment_id']);

        foreach ($logs->getItems() as $item) {
            if (!isset($incrementIds[$item->getKlarnaId()])) {
                continue;
            }
            $item->setIncrementId($incrementIds[$item->getKlarnaId()]);

            try {
                $this->logRepository->save($item);
            } catch (CouldNotSaveException $e) {
                $this->logger->error(
                    'Could not save the log information to the database. Reason: ' . $e->getMessage()
                );
            }
        }
    }
}
