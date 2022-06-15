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
        $this->queryBuilder->getConnection()->executeQuery('REPLACE INTO tx_pagewarmup_queue SET url = :url, done = :done', ['url' => $url, 'done' => 0]);
    }

    public function queueMany(array $urls): void
    {
        foreach ($urls as $url) {
            $this->queue($url);
        }
    }

    public function provide(): \Generator
    {
        $queryBuilder = clone $this->queryBuilder;
        $result = $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_queue')
            ->where($queryBuilder->expr()->eq('done', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->execute();
        while ($url = $result->fetchOne()) {
            yield $url;
            $queryBuilder->getConnection()->update('tx_pagewarmup_queue', ['done' => 1], ['url' => $url]);
        }
        $queryBuilder->getConnection()->truncate('tx_pagewarmup_queue');
    }

    public function getTotalCount(): int
    {
        $queryBuilder = clone $this->queryBuilder;
        return (int)$queryBuilder
            ->count('url')
            ->from('tx_pagewarmup_queue')
            ->execute()
            ->fetchOne();
    }

    public function getDoneCount(): int
    {
        $queryBuilder = clone $this->queryBuilder;
        return (int)$queryBuilder
            ->count('url')
            ->from('tx_pagewarmup_queue')
            ->where($queryBuilder->expr()->eq('done', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->execute()
            ->fetchOne();
    }

    public function getProgress(): float
    {
        $totalCount = $this->getTotalCount();
        if ($totalCount === 0) {
            return 100.0;
        }
        $doneCount = $this->getDoneCount();
        return ($doneCount / $totalCount) * 100;
    }
}
