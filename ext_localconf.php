<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['frontend'] = \Smic\PageWarmup\Cache\Frontend\VariableFrontendWithWarmupReservation::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Smic\PageWarmup\Task\WarmupQueueWorkerTask::class] = [
    'extension' => 'page_warmup',
    'title' => 'Page Cache Warmup Queue Worker',
    'additionalFields' => \Smic\PageWarmup\Task\WarmupQueueWorkerTaskAdditionalFieldProvider::class,
];

if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Smic']['PageWarmup']['writerConfiguration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['Smic']['PageWarmup']['writerConfiguration'] = [
        \Psr\Log\LogLevel::INFO => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFile' => 'typo3temp/var/logs/cacheFlushes.log',
            ],
        ],
    ];
}
