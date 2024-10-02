<?php

namespace App\Commands\Plugins;

use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;

class SetupExposeProToken implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;


    public function __invoke(string $token)
    {
        if (!$this->exposePlatformSetup()) return;

        $this->token = $token;

        if ($this->isProToken() && $this->hasTeamDomains()) {
            return (new SetUpExposeDefaultDomain)($token);
        } else {
            return (new SetUpExposeDefaultServer)($token);
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
