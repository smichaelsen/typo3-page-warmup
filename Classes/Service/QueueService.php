<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Service;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class QueueService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function queue(string $url): void
    {
        $this->getQueryBuilder()->getConnection()->executeQuery('REPLACE INTO tx_pagewarmup_queue SET url = :url, done = :done, queued = :queued', ['url' => $url, 'done' => 0, 'queued' => $GLOBALS['EXEC_TIME']]);
    }

    public function queueMany(array $urls): void
    {
        foreach ($urls as $url) {
            $this->queue($url);
        }
    }

    public function provide(): \Generator
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_queue')
            ->where($queryBuilder->expr()->eq('done', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)))
            ->setMaxResults(1);
        while (true) {
            $result = $queryBuilder->executeQuery();
            $url = $result->fetchOne();
            if ($url === false) {
                break;
            }
            $queryBuilder->getConnection()->update('tx_pagewarmup_queue', ['done' => 1], ['url' => $url]);
            yield $url;
        }
        $queryBuilder->getConnection()->truncate('tx_pagewarmup_queue');
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder();
        return (int)$queryBuilder
            ->count('url')
            ->from('tx_pagewarmup_queue')
            ->executeQuery()
            ->fetchOne();
    }

    public function getDoneCount(): int
    {
        $queryBuilder = $this->getQueryBuilder();
        return (int)$queryBuilder
            ->count('url')
            ->from('tx_pagewarmup_queue')
            ->where($queryBuilder->expr()->eq('done', $queryBuilder->createNamedParameter(1, ParameterType::INTEGER)))
            ->executeQuery()
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

    public function getQueueStartTimestamp(): ?int
    {
        $queryBuilder = $this->getQueryBuilder();
        $timestamp = $queryBuilder
            ->select('queued')
            ->from('tx_pagewarmup_queue')
            ->where(
                $queryBuilder->expr()->gt('queued', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER))
            )
            ->orderBy('queued', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        if ($timestamp === false) {
            return null;
        }
        return (int)$timestamp;
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tx_pagewarmup_queue');
    }
}
