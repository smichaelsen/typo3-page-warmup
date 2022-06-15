<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Cache\Frontend;

use Psr\Http\Message\ServerRequestInterface;
use Smic\PageWarmup\Service\QueueService;
use Smic\PageWarmup\Service\WarmupReservationService;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class VariableFrontendWithWarmupReservation extends VariableFrontend
{
    public function set($entryIdentifier, $variable, array $tags = [], $lifetime = null)
    {
        parent::set($entryIdentifier, $variable, $tags, $lifetime);
        /** @var ?ServerRequestInterface $request */
        $request = $GLOBALS['ORIGINAL_REQUEST'] ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (
            !is_array($variable) ||
            !isset($variable['page_id']) ||
            !isset($variable['pageTitleInfo']) ||
            !$request instanceof ServerRequestInterface ||
            count($tags) === 0
        ) {
            // the cache entry doesn't *look* like a page is being cached => nothing to do
            return;
        }

        $warmupReservationService = GeneralUtility::makeInstance(WarmupReservationService::class);
        $warmupReservationService->addReservations($this->getIdentifier(), (string)$request->getUri(), $tags);
    }

    public function flush()
    {
        parent::flush();
        $warmupReservationService = GeneralUtility::makeInstance(WarmupReservationService::class);
        $urls = $warmupReservationService->collectAllReservations($this->getIdentifier());
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        $queueService->queueMany($urls);
    }

    public function flushByTag($tag)
    {
        parent::flushByTag($tag);
        $warmupReservationService = GeneralUtility::makeInstance(WarmupReservationService::class);
        $urls = $warmupReservationService->collectReservationsByCacheTags($this->getIdentifier(), [$tag]);
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        $queueService->queueMany($urls);
    }

    public function flushByTags(array $tags)
    {
        parent::flushByTags($tags);
        $tags = array_unique($tags);
        $warmupReservationService = GeneralUtility::makeInstance(WarmupReservationService::class);
        $urls = $warmupReservationService->collectReservationsByCacheTags($this->getIdentifier(), $tags);
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        $queueService->queueMany($urls);
    }
}
