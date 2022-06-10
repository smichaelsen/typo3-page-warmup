<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Smic\PageWarmup\Task\WarmupQueueWorkerTask::class] = [
    'extension' => 'page_warmup',
    'title' => 'Page Cache Warmup Queue Worker',
    'additionalFields' => \Smic\PageWarmup\Task\WarmupQueueWorkerTaskAdditionalFieldProvider::class,
];
