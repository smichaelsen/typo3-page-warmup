<?php

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['frontend'] = \Smic\PageWarmup\Cache\Frontend\VariableFrontendWithWarmupReservation::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Smic\PageWarmup\Task\WarmupQueueMakerTask::class] = [
    'extension' => 'page_warmup',
    'title' => 'Page Cache Warmup Queue Maker',
    'additionalFields' => \Smic\PageWarmup\Task\WarmupQueueMakerTask::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Smic\PageWarmup\Task\WarmupQueueWorkerTask::class] = [
    'extension' => 'page_warmup',
    'title' => 'Page Cache Warmup Queue Worker',
    'additionalFields' => \Smic\PageWarmup\Task\WarmupQueueWorkerTaskAdditionalFieldProvider::class,
];
