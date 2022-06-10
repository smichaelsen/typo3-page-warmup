<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Task;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class WarmupQueueWorkerTask extends AbstractTask
{
    private int $timeLimit = 60;

    public function execute(): bool
    {
        $this->workThroughQueueWithTimeLimit(60);
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

    public function workThroughQueueWithTimeLimit(int $seconds)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_pagewarmup_queue');
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        $result = $queryBuilder
            ->select('url')
            ->from('tx_pagewarmup_queue')
            ->execute();

        $end = time() + $seconds;
        while (time() < $end && $url = $result->fetchOne()) {
            $requestFactory->request($url);
            $queryBuilder->getConnection()->delete('tx_pagewarmup_queue', ['url' => $url]);
        }
    }
}
