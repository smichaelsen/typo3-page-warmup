<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Task;

use GuzzleHttp\Exception\BadResponseException;
use Smic\PageWarmup\Service\QueueService;
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
            try {
                $requestFactory->request($url);
            } catch (BadResponseException $e) {
                // ignore
            }
            if (time() >= $end) {
                return;
            }
        }
    }

    public function getProgress(): float
    {
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        return max(0.01, $queueService->getProgress());
    }

    public function getAdditionalInformation(): string
    {
        $queueService = GeneralUtility::makeInstance(QueueService::class);
        $totalCount = $queueService->getTotalCount();
        if ($totalCount === 0) {
            return '';
        }
        $message = sprintf(
            'Warmed up %s of %s URLs that are in the current queue.',
            number_format($queueService->getDoneCount()),
            number_format($totalCount)
        );
        $queueStartTimestamp = $queueService->getQueueStartTimestamp();
        if ($queueStartTimestamp !== null) {
            $message .= sprintf(
                ' Started %s.',
                date('d.m.Y H:i', $queueStartTimestamp)
            );
        }
        return $message;
    }
}
