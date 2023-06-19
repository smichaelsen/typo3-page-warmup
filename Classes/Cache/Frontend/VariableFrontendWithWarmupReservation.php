<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Cache\Frontend;

use Psr\Http\Message\ServerRequestInterface;
use Smic\PageWarmup\Service\QueueMakerService;
use Smic\PageWarmup\Service\WarmupReservationService;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class VariableFrontendWithWarmupReservation extends VariableFrontend
{
    private array $extensionConfiguration = [];

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

        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('page_warmup');
        $whitelistedGetParams = GeneralUtility::trimExplode(',', $this->getWhitelistedParams());
        $getParams = $request->getQueryParams();
        $warmupReservationService = GeneralUtility::makeInstance(WarmupReservationService::class);

        if ($this->areAllGetParamsAllowed($whitelistedGetParams, array_keys($getParams))) {
            $warmupReservationService->addReservations($this->getIdentifier(), (string)$request->getUri(), $tags);
        } else {
            $uri = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . $request->getUri()->getPath();
            $warmupReservationService->addReservations($this->getIdentifier(), $uri, $tags);
        }
    }

    public function flush()
    {
        parent::flush();

        $queueMakerService = GeneralUtility::makeInstance(QueueMakerService::class);
        $queueMakerService->addToQueue($this->getIdentifier(), QueueMakerService::CACHE_ENTRY_TYPE_ALL, null);
    }

    public function flushByTag($tag)
    {
        parent::flushByTag($tag);

        $queueMakerService = GeneralUtility::makeInstance(QueueMakerService::class);
        $queueMakerService->addToQueue($this->getIdentifier(), QueueMakerService::CACHE_ENTRY_TYPE_TAG, $tag);
    }

    public function flushByTags(array $tags)
    {
        parent::flushByTags($tags);

        $queueMakerService = GeneralUtility::makeInstance(QueueMakerService::class);

        foreach ($tags as $tag) {
            $queueMakerService->addToQueue($this->getIdentifier(), QueueMakerService::CACHE_ENTRY_TYPE_TAGS, $tag);
        }
    }

    private function areAllGetParamsAllowed(array $whitelistedGetParams, array $getParams): bool
    {
        return array_intersect($getParams, $whitelistedGetParams) === $getParams;
    }

    private function getWhitelistedParams(): string
    {
        return $this->extensionConfiguration['getparams']['whitelisterparams'] ?? '';
    }
}
