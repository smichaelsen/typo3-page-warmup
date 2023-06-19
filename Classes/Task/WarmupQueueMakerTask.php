<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Task;

use Smic\PageWarmup\Service\QueueMakerService;
use Smic\PageWarmup\Service\QueueService;
use Smic\PageWarmup\Service\WarmupReservationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class WarmupQueueMakerTask extends AbstractTask
{
    public function execute(): bool
    {
        $this->workThroughAndCreateQueue();
        return true;
    }

    public function workThroughAndCreateQueue(): void
    {
        $queueMakerService = GeneralUtility::makeInstance(QueueMakerService::class);
        $queue = $queueMakerService->getQueue();

        foreach ($queue as $item) {
            $queueMakerService->markItemAsDone($item['cache_tag']);
            $this->addToQueue($item);
        }
    }

    private function addToQueue(array $item): void
    {
        $warmupReservationService = GeneralUtility::makeInstance(WarmupReservationService::class);
        if ($item['type'] > QueueMakerService::CACHE_ENTRY_TYPE_ALL) {
            $urls = $warmupReservationService->collectReservationsByCacheTags($item['cache'], [$item['cache_tag']]);
        } else {
            $urls = $warmupReservationService->collectAllReservations($item['cache']);
        }
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        $queueService->queueMany($urls);
    }
}
