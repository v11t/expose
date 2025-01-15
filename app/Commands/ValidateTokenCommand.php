<?php

namespace Expose\Client\Commands;

use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Traits\FetchesPlatformData;
use LaravelZero\Framework\Commands\Command;
use function Expose\Common\banner;
use function Expose\Common\error;
use function Expose\Common\info;

class ValidateTokenCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'token:validate {token?}';

    protected $description = 'Validate the current Expose token.';

    protected string $token;

    public function handle()
    {
        if (!$this->exposePlatformSetup()) return;

        if (!$this->option('no-interaction')) {
            banner();
        }

        $this->token = $this->argument('token') ?? config('expose.auth_token');

        $exposeToken = $this->exposeToken();

        if ($exposeToken->isInvalid()) {
            error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit expose.dev to create your free account.");
            exit;
        } else {
            if (!$this->option('no-interaction')) {
                info("Token $this->token is valid." . ($exposeToken->isPro() ? " Thanks for using Expose Pro! 💎" : ""));
            }
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }


}
