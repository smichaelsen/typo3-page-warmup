<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Task;

use GuzzleHttp\Exception\BadResponseException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Smic\PageWarmup\Events\PrepareWarmupRequestOptions;
use Smic\PageWarmup\Service\QueueService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class WarmupQueueWorkerTask extends AbstractTask implements ProgressProviderInterface
{
    /**
     * Names warmup traffic in the access log of the site being warmed up, so it can be told apart
     * from real visitors. Listeners on PrepareWarmupRequestOptions can replace it.
     */
    private const DEFAULT_USER_AGENT = 'TYPO3-PageWarmup';

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
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $end = time() + $seconds;

        $event = new PrepareWarmupRequestOptions();
        $event->setRequestOptions(['headers' => ['User-Agent' => self::DEFAULT_USER_AGENT]]);
        $requestOptions = $eventDispatcher->dispatch($event)->getRequestOptions();

        foreach ($queueService->provide() as $url) {
            try {
                $requestFactory->request($url, 'GET', $requestOptions);
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
