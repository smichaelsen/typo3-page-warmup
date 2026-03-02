<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Tests\Functional\Cache\Frontend;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WarmupReservationOnPageCacheTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/page_warmup',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'page_warmup' => [
                'getparams' => [
                    'whitelisterparams' => 'id',
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/RootPage.csv');
        $this->setUpFrontendRootPage(
            1,
            ['EXT:page_warmup/Tests/Functional/Cache/Frontend/Fixtures/setup.typoscript'],
        );
        $this->setUpSite(1);
    }

    #[Test]
    public function requestingCacheablePageCreatesWarmupReservationRecord(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters(['id' => 1]),
        );

        self::assertSame(200, $response->getStatusCode());

        $reservations = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_pagewarmup_reservation')
            ->executeQuery('SELECT cache, url, cache_tag FROM tx_pagewarmup_reservation')
            ->fetchAllAssociative();

        self::assertNotEmpty($reservations);
        self::assertStringContainsString('id=1', (string)$reservations[0]['url']);
        self::assertNotSame('', (string)$reservations[0]['cache_tag']);
    }

    private function setUpSite(int $rootPageId): void
    {
        $configuration = [
            'rootPageId' => $rootPageId,
            'base' => '/',
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'websiteTitle' => 'Site EN',
                    'navigationTitle' => '',
                    'hreflang' => '',
                    'direction' => '',
                    'flag' => 'us',
                ],
            ],
            'errorHandling' => [],
            'routes' => [],
        ];

        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        GeneralUtility::writeFile(
            $this->instancePath . '/typo3conf/sites/testing/config.yaml',
            Yaml::dump($configuration, 99, 2),
        );
    }
}
