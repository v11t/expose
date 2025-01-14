<?php

namespace Expose\Client\Commands;


use Expose\Client\Commands\Support\ValidateExposeToken;
use Expose\Client\Contracts\FetchesPlatformDataContract;
use Expose\Client\Support\TokenNodeVisitor;
use Expose\Client\Traits\FetchesPlatformData;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

use function Expose\Common\banner;
use function Expose\Common\error;
use function Expose\Common\info;

class StoreAuthenticationTokenCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'token {token?} {--clean}';

    protected $description = 'Set the authentication token to use with Expose.';

    protected string $token = '';

    public function handle()
    {
        $this->token = $this->argument('token');

        if (is_null($this->token) && config('expose.auth_token') !== null) {
            return $this->call('token:get', ['--no-interaction' => $this->option('no-interaction')]);
        }

        if (!$this->option('no-interaction')) {
            banner();
        }

        if ($this->exposeToken()->isInvalid()) {
            error("Token $this->token is invalid. Please check your token and try again. If you don't have a token, visit <a href='https://expose.dev'>expose.dev</a> to create your free account.");

            if ($this->exposeToken()->hasError() && $this->getOutput()->isVerbose()) {
                info();
                info($this->exposeToken()->getError());
            }

            exit;
        }

        $this->rememberPreviousSetup();

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'config.php',
        ]);

        if (!file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'), $this->argument('token'));
        } else {
            $updatedConfigFile = $this->modifyConfigurationFile($configFile, $this->argument('token'));
        }

        file_put_contents($configFile, $updatedConfigFile);

        if (!$this->option('no-interaction')) {

            info("Setting up new Expose token <span class='font-bold'>$this->token</span>...");

            (new SetupExposeProToken)($this->token);
        } else {
            info("Token set to $this->token.");
        }

    }

    protected function rememberPreviousSetup(): void
    {

        $previousSetup = [
            'token' => config('expose.auth_token'),
            'default_server' => config('expose.default_server'),
            'default_domain' => config('expose.default_domain'),
        ];

        $previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'previous_setup.json',
        ]);

        file_put_contents($previousSetupPath, json_encode($previousSetup));
    }

    protected function modifyConfigurationFile(string $configFile, string $token)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new TokenNodeVisitor($token));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
