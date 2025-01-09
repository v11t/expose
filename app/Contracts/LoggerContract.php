<?php

namespace Expose\Client\Contracts;

use Expose\Client\Logger\LoggedRequest;

interface LoggerContract
{
    public function synchronizeRequest(LoggedRequest $loggedRequest): void;

    public function synchronizeResponse(LoggedRequest $loggedRequest, string $rawResponse): void;
}
