<?php

namespace Tests\Support;

trait CanSkipTests
{
    public function markIncompleteIfMySQL($message = 'Test skipped due to database driver being MySQL.')
    {
        if (config('database.default') === 'mysql') {
            $this->markTestIncomplete($message);
        }
    }

    public function markIncompleteIfSqlite($message = 'Test skipped due to database driver being sqlite.')
    {
        $connection = config('database.default');

        if (config("database.connections.{$connection}.driver") === 'sqlite') {
            $this->markTestIncomplete($message);
        }
    }
}
