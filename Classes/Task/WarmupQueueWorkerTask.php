<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Task;

use Smic\PageWarmup\Service\QueueService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class WarmupQueueWorkerTask extends AbstractTask implements ProgressProviderInterface
{
    private int $timeLimit = 60;

    public function execute(): bool
    {
        $this->workThroughQueueWithTimeLimit($this->timeLimit);
        return true;
    }

    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(int $timeLimit): void
    {
        $this->timeLimit = $timeLimit;
    }

    public function workThroughQueueWithTimeLimit(int $seconds): void
    {
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $end = time() + $seconds;

        foreach ($queueService->provide() as $url) {
            if (time() >= $end) {
                return;
            }
            $requestFactory->request($url);
        }
    }

    public function getProgress(): float
    {
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        return $queueService->getProgress();
    }
}
