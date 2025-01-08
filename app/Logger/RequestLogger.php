<?php

namespace Expose\Client\Logger;

use Laminas\Http\Request;
use Laminas\Http\Response;
use React\Http\Browser;

class RequestLogger
{
    protected array $requests = [];

    protected Browser $client;

    protected CliRequestLogger $cliRequestLogger;
    protected DatabaseRequestLogger $databaseRequestLogger;

    public function __construct(Browser $browser, CliRequestLogger $cliRequestLogger, DatabaseRequestLogger $databaseRequestLogger)
    {
        $this->client = $browser;
        $this->cliRequestLogger = $cliRequestLogger;
        $this->databaseRequestLogger = $databaseRequestLogger;
    }

    public function findLoggedRequest(string $id): ?LoggedRequest
    {
        return collect($this->requests)->first(function (LoggedRequest $loggedRequest) use ($id) {
            return $loggedRequest->id() === $id;
        });
    }

    public function logRequest(string $rawRequest, Request $request): LoggedRequest
    {
        $loggedRequest = new LoggedRequest($rawRequest, $request);

        array_unshift($this->requests, $loggedRequest);

        $this->requests = array_slice($this->requests, 0, config('expose.max_logged_requests', 10));

        $this->cliRequestLogger->logRequest($loggedRequest);

        $this->databaseRequestLogger->logRequest($loggedRequest);

        $this->pushLoggedRequest($loggedRequest);

        return $loggedRequest;
    }

    public function logResponse(Request $request, string $rawResponse)
    {
        $requests = $this->databaseRequestLogger->getData();

        $exposeRequestId = $request->getHeader("x-expose-request-id") ? $request->getHeader("x-expose-request-id")->getFieldValue() : null;

        if(!$exposeRequestId) {
            return;
        }

        $loggedRequest = collect($requests)->filter(function (LoggedRequest $loggedRequest) use ($exposeRequestId) {
            return $loggedRequest->id() === $exposeRequestId;
        })->first();

        $loggedRequest->setResponse($rawResponse, Response::fromString($rawResponse));
        $loggedRequest->setStopTime();

        $this->cliRequestLogger->logRequest($loggedRequest);
        $this->databaseRequestLogger->logRequest($loggedRequest);

        $this->pushLoggedRequest($loggedRequest);
    }

    public function getData(): array
    {
        return $this->requests;
    }

    public function clear()
    {
        $this->requests = [];
    }

    public function pushLoggedRequest(LoggedRequest $request)
    {
        $this
            ->client
            ->post(
                'http://127.0.0.1:4040/api/logs',
                ['Content-Type' => 'application/json'],
                json_encode($request, JSON_INVALID_UTF8_IGNORE)
            );
    }
}
