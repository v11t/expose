<?php

namespace App\Commands\Plugins;


use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\select;
use function Termwind\render;

class SetUpExposeDefaultDomain implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected string $token;

    public function __invoke(string $token)
    {
        if (!$this->exposePlatformSetup()) return;

        $this->token = $token;

        render('<div class="ml-3 mt-1 font-bold">Default Domain</div>');
        render('<div class="ml-3">Use your teams custom whitelabel domains for easier subdomain management and better links.</div>');

        if ($this->isProToken()) {
            $domains = $this->getTeamDomains();

            if ($domains->isNotEmpty()) {
                $domain = select(
                    label: 'What default domain would you like to use?',
                    options: $domains->mapWithKeys(function ($domain) {
                        return [
                            $domain['name'] =>  $domain['name'] . ' [' . $domain['server'] . ']'
                        ];
                    }),
                    hint: "You can use `expose default-domain` to change this setting."
                );

                if ($domain) {
                    $server = $domains->firstWhere('name', $domain)['server'];
                    Artisan::call("default-domain $domain --server=$server");
                }
            }

            else {
                render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">No custom domains found. You can add custom domains in the Expose dashboard.</div>');
            }
        }
        else {
            render('<div class="ml-3 mb-1">To access to custom domains, upgrade to <a href="https://expose.dev">Expose Pro</a>.');
        }
    }

    public function getToken()
    {
        return $this->token;
    }
}
