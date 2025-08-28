<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Service;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class WarmupReservationService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function addReservation(string $cacheIdentifier, string $url, string $cacheTag): void
    {
        $connection = $this->getQueryBuilder()->getConnection();
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
        $queryBuilder = $this->getQueryBuilder();
        $where = [
            $queryBuilder->expr()->eq('cache', $queryBuilder->createNamedParameter($cacheIdentifier)),
        ];
        $reservations = $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_reservation')
            ->where(...$where)
            ->executeQuery()
            ->fetchAllAssociative();
        $this->getQueryBuilder()
            ->delete('tx_pagewarmup_reservation')
            ->where(...$where)
            ->executeStatement();
        return array_unique(array_column($reservations, 'url'));
    }

    public function collectReservationsByCacheTags(string $cacheIdentifier, array $cacheTags): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $where = [
            $queryBuilder->expr()->eq('cache', $queryBuilder->createNamedParameter($cacheIdentifier)),
            $queryBuilder->expr()->in('cache_tag', $queryBuilder->createNamedParameter($cacheTags, ArrayParameterType::STRING)),
        ];
        $reservations = $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_reservation')
            ->where(...$where)
            ->executeQuery()
            ->fetchAllAssociative();
        $this->getQueryBuilder()
            ->delete('tx_pagewarmup_reservation')
            ->where(...$where)
            ->executeStatement();
        $urls = array_unique(array_column($reservations, 'url'));
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete('tx_pagewarmup_reservation')
            ->where($queryBuilder->expr()->in('url', $queryBuilder->createNamedParameter($urls, ArrayParameterType::STRING)))
            ->executeQuery();
        return $urls;
    }

    public function flushReservations(string $cacheIdentifier): void
    {
        $connection = $this->getQueryBuilder()->getConnection();
        $connection->delete(
            'tx_pagewarmup_reservation',
            ['cache' => $cacheIdentifier],
        );
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('tx_pagewarmup_reservation');
    }
}
