<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueueMakerService
{
    private QueryBuilder $queryBuilder;

    const CACHE_ENTRY_TYPE_ALL = 0;
    const CACHE_ENTRY_TYPE_TAG = 1;
    const CACHE_ENTRY_TYPE_TAGS = 2;

    public function __construct(ConnectionPool $connectionPool = null)
    {
        if ($connectionPool === null) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        $this->queryBuilder = $connectionPool->getQueryBuilderForTable('tx_pagewarmup_queue_maker');
    }

    public function addToQueue(string $cacheIdentifier, int $type, ?string $cacheTag): void
    {
        $connection = $this->queryBuilder->getConnection();
        $connection->insert(
            'tx_pagewarmup_queue_maker',
            [
                'cache' => $cacheIdentifier,
                'type' => $type,
                'cache_tag' => $cacheTag,
            ],
        );
    }

    public function getQueue(): array
    {
        $queryBuilder = clone $this->queryBuilder;
        $queue = $queryBuilder
            ->select('*')
            ->from('tx_pagewarmup_queue_maker')
            ->executeQuery()
            ->fetchAllAssociative();

        return $queue;
    }

    public function markItemAsDone(string $cacheIdentifier): void
    {
        $connection = $this->queryBuilder->getConnection();
        $connection->delete(
            'tx_pagewarmup_queue_maker',
            ['cache_tag' => $cacheIdentifier],
        );
    }
}
