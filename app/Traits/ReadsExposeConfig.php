<?php
namespace Expose\Client\Traits;

trait ReadsExposeConfig
{
    public function getDatabasePath(): string {
        return config('database.connections.sqlite.database');
    }

    public function getVersion(): string {
        return config('app.version');
    }
}
