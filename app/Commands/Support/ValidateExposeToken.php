<?php

namespace Expose\Client\Commands\Support;

use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Traits\FetchesPlatformData;
use function Expose\Common\error;

class ValidateExposeToken implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->exposePlatformSetup()) return;

        $this->token = $token;

        $exposeToken = $this->exposeToken();

        if ($exposeToken->isInvalid()) {
            error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit <a href='https://expose.dev'>expose.dev</a> to create your free account.");
            exit;
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
