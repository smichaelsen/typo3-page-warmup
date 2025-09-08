<?php

declare(strict_types=1);

namespace Smic\PageWarmup\Events;

class PrepareWarmupRequestOptions
{
    private array $requestOptions = [];

    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }

    public function setRequestOptions(array $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }
}
