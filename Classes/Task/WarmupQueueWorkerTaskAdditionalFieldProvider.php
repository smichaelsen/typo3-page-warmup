<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Task;

use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class WarmupQueueWorkerTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        /** @var ?WarmupQueueWorkerTask $task */
        $taskInfo['timeLimit'] = $task instanceof WarmupQueueWorkerTask ? $task->getTimeLimit() : 60;

        return [
            'timeLimit' => [
                'code' => '<input type="number" class="form-control" name="tx_scheduler[timeLimit]" value="' . $taskInfo['timeLimit'] . '" />',
                'label' => 'Time limit in seconds',
            ],
        ];
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        return true;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        /** @var WarmupQueueWorkerTask $task */
        $task->setTimeLimit((int)$submittedData['timeLimit']);
    }
}
