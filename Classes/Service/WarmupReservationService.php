<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Service;

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WarmupReservationService
{
    private QueryBuilder $queryBuilder;

    public function __construct(ConnectionPool $connectionPool = null)
    {
        if ($connectionPool === null) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        $this->queryBuilder = $connectionPool->getQueryBuilderForTable('tx_pagewarmup_reservation');
    }

    public function addReservation(string $cacheIdentifier, string $url, string $cacheTag): void
    {
        $connection = $this->queryBuilder->getConnection();
        $connection->insert(
            'tx_pagewarmup_reservation',
            [
                'cache' => $cacheIdentifier,
                'url' => $url,
                'cache_tag' => $cacheTag,
            ],
        );
    }

    public function addReservations(string $cacheIdentifier, string $url, array $cacheTags): void
    {
        $cacheTags = array_unique($cacheTags);
        foreach ($cacheTags as $cacheTag) {
            $this->addReservation($cacheIdentifier, $url, $cacheTag);
        }
    }

    public function collectAllReservations(string $cacheIdentifier): array
    {
        $queryBuilder = clone $this->queryBuilder;
        $where = [
            $queryBuilder->expr()->eq('cache', $queryBuilder->createNamedParameter($cacheIdentifier)),
        ];
        $reservations = $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_reservation')
            ->where(...$where)
            ->execute()
            ->fetchAllAssociative();
        $queryBuilder
            ->delete('tx_pagewarmup_reservation')
            ->where(...$where)
            ->execute();
        $urls = array_unique(array_column($reservations, 'url'));
        return $urls;
    }

    public function collectReservationsByCacheTags(string $cacheIdentifier, array $cacheTags): array
    {
        $queryBuilder = clone $this->queryBuilder;
        $where = [
            $queryBuilder->expr()->eq('cache', $queryBuilder->createNamedParameter($cacheIdentifier)),
            $queryBuilder->expr()->in('cache_tag', $queryBuilder->createNamedParameter($cacheTags, Connection::PARAM_STR_ARRAY)),
        ];
        $reservations = $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_reservation')
            ->where(...$where)
            ->execute()
            ->fetchAllAssociative();
        $queryBuilder
            ->delete('tx_pagewarmup_reservation')
            ->where(...$where)
            ->execute();
        $urls = array_unique(array_column($reservations, 'url'));
        $queryBuilder = clone $this->queryBuilder;
        $queryBuilder
            ->delete('tx_pagewarmup_reservation')
            ->where($queryBuilder->expr()->in('url', $queryBuilder->createNamedParameter($urls, Connection::PARAM_STR_ARRAY)))
            ->execute();
        return $urls;
    }

    public function flushReservations(string $cacheIdentifier): void
    {
        $connection = $this->queryBuilder->getConnection();
        $connection->delete(
            'tx_pagewarmup_reservation',
            ['cache' => $cacheIdentifier],
        );
    }
}
