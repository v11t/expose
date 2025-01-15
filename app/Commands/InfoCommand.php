<?php

namespace Expose\Client\Commands;

use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Traits\FetchesPlatformData;
use Exception;
use Expose\Client\Traits\ReadsExposeConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use function Expose\Common\banner;
use function Expose\Common\headline;
use function Expose\Common\lineTable;
use function Expose\Common\lineTableLabel;

class InfoCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;
    use ReadsExposeConfig;

    protected $signature = 'info {--json}';

    protected $description = 'Displays the current configuration for Expose.';

    public function handle()
    {

        if (!$this->option('json')) {
            banner();
        }

        $configuration = [];

        $configuration = [
            "token" => config('expose.auth_token'),
            "default_server" => config('expose.default_server'),
            "default_domain" => config('expose.default_domain'),
            "plan" => $this->isProToken() ? "pro" : "free",
            "version" => $this->getVersion(),
            "latency" => $this->checkLatency(config('expose.default_server')) . "ms"
        ];

        if ($this->option('json')) {
            $this->line(json_encode($configuration));
            return;
        }

        headline('Configuration');

        $configuration = collect($configuration)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        lineTable($configuration);
    }

    protected function checkLatency(string $server): int
    {

        if ($server === "free") {
            $host = "sharedwithexpose.com";
        } else {
            $host = "expose.{$server}.sharedwithexpose.com";
        }

        try {
            $result = Http::timeout(5)->get($host);
            return round($result->handlerStats()['connect_time'] * 1000);
        } catch (Exception $e) {
            if ($this->option("verbose")) {
                warning("Error while checking latency: {$e->getMessage()}");
            }

            return 999;
        }
    }

    public function getToken(): string
    {
        return config('expose.auth_token');
    }
}
