<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueueService
{
    private QueryBuilder $queryBuilder;

    public function __construct(ConnectionPool $connectionPool = null)
    {
        if ($connectionPool === null) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        $this->queryBuilder = $connectionPool->getQueryBuilderForTable('tx_pagewarmup_queue');
    }

    public function queue(string $url): void
    {
        $this->queryBuilder->getConnection()->executeQuery('INSERT IGNORE tx_pagewarmup_queue SET url = :url', ['url' => $url]);
    }

    public function queueMany(array $urls): void
    {
        foreach ($urls as $url) {
            $this->queue($url);
        }
    }
}
