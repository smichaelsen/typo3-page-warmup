<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use Smic\PageWarmup\Service\WarmupReservationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WarmupReservationServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/page_warmup',
    ];

    #[Test]
    public function collectingReservationsForUnreservedCacheTagsLeavesEveryReservationInPlace(): void
    {
        $subject = $this->get(WarmupReservationService::class);
        $subject->addReservations('pages', 'https://example.com/a', ['pageId_1']);

        self::assertSame([], $subject->collectReservationsByCacheTags('pages', ['pageId_2']));
        self::assertSame(
            [
                ['cache' => 'pages', 'url' => 'https://example.com/a', 'cache_tag' => 'pageId_1'],
            ],
            $this->getAllReservations(),
        );
    }

    #[Test]
    public function collectingReservationsRemovesEveryReservationOfTheCollectedUrls(): void
    {
        $subject = $this->get(WarmupReservationService::class);
        $subject->addReservations('pages', 'https://example.com/a', ['pageId_1', 'tt_content_5']);
        $subject->addReservations('pages', 'https://example.com/b', ['pageId_2']);

        self::assertSame(
            ['https://example.com/a'],
            array_values($subject->collectReservationsByCacheTags('pages', ['pageId_1'])),
        );
        self::assertSame(
            [
                ['cache' => 'pages', 'url' => 'https://example.com/b', 'cache_tag' => 'pageId_2'],
            ],
            $this->getAllReservations(),
        );
    }

    #[Test]
    public function collectingReservationsRemovesTheCollectedUrlsFromEveryCache(): void
    {
        $subject = $this->get(WarmupReservationService::class);
        $subject->addReservations('pages', 'https://example.com/a', ['pageId_1']);
        $subject->addReservations('rootline', 'https://example.com/a', ['pageId_1']);

        self::assertSame(
            ['https://example.com/a'],
            array_values($subject->collectReservationsByCacheTags('pages', ['pageId_1'])),
        );
        // The url is being warmed up, so the rendering that follows registers whatever the other
        // caches need. Their stale reservations for it go, which is why the sweep carries no cache
        // condition and needs an index on url of its own.
        self::assertSame([], $this->getAllReservations());
    }

    /**
     * @return array<int, array{cache: string, url: string, cache_tag: string}>
     */
    private function getAllReservations(): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_pagewarmup_reservation')
            ->executeQuery('SELECT cache, url, cache_tag FROM tx_pagewarmup_reservation ORDER BY url, cache_tag')
            ->fetchAllAssociative();
    }
}
