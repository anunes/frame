<?php

namespace app\console;

class Serve
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        $port = $arguments[0] ?? '8000';
        $host = '127.0.0.1';

        $documentRoot = BASE_PATH . '/public';

        echo "\033[32m";
        echo "  ___                       \n";
        echo " | __|_ _ __ _ _ __  ___    \n";
        echo " | _| '_/ _` | '  \\/ -_)   \n";
        echo " |_||_| \\__,_|_|_|_\\___|   \n";
        echo "\033[0m\n";
        echo "\033[33mFrame Development Server\033[0m\n\n";
        echo "Server started on \033[32mhttp://{$host}:{$port}\033[0m\n";
        echo "Document root: \033[36m{$documentRoot}\033[0m\n";
        echo "\nPress Ctrl+C to stop the server\n\n";

        // Start PHP built-in server
        $command = sprintf(
            'php -S %s:%s -t %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($documentRoot)
        );

        passthru($command);
    }
}
