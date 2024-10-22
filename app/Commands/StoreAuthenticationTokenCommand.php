<?php

namespace Expose\Client\Commands;

use Expose\Client\Commands\Concerns\RendersBanner;
use Expose\Client\Support\TokenNodeVisitor;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use function Termwind\render;

class StoreAuthenticationTokenCommand extends Command
{
    use RendersBanner;

    protected $signature = 'token {token?} {--clean}';

    protected $description = 'Set the authentication token to use with Expose.';

    public function handle()
    {
        $token = $this->argument('token');

        if (is_null($token) && config('expose.auth_token') !== null) {
            return $this->call('token:get', ['--no-interaction' => $this->option('no-interaction')]);
        }

        $this->rememberPreviousSetup();

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'config.php',
        ]);

        if (! file_exists($configFile)) {
            @mkdir(dirname($configFile), 0777, true);
            $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'), $this->argument('token'));
        } else {
            $updatedConfigFile = $this->modifyConfigurationFile($configFile, $this->argument('token'));
        }

        file_put_contents($configFile, $updatedConfigFile);

        if (!$this->option('no-interaction')) {

            $this->renderBanner();
            render("<div class='ml-3'>Setting up new Expose token <span class='font-bold'>$token</span>...</div>");

            (new SetupExposeProToken)($token);
        }
        else {
            $this->line("Token set to $token.");
        }


        return;
    }

    protected function rememberPreviousSetup() {

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
}
