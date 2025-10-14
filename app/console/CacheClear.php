<?php

namespace app\console;

class CacheClear
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        echo "Clearing application cache...\n\n";

        $cleared = 0;
        $failed = 0;

        // Clear view template cache
        $viewCachePath = BASE_PATH . '/storage/cache/views';
        if (is_dir($viewCachePath)) {
            $files = glob($viewCachePath . '/*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $cleared++;
                    } else {
                        $failed++;
                    }
                }
            }
            echo "\033[32m✓\033[0m View cache cleared ({$cleared} files)\n";
        }

        // Clear session files (optional - be careful with this in production)
        $sessionPath = session_save_path();
        if (!empty($sessionPath) && is_dir($sessionPath)) {
            $sessionFiles = glob($sessionPath . '/sess_*');
            $sessionCleared = 0;
            foreach ($sessionFiles as $file) {
                if (is_file($file) && time() - filemtime($file) > 3600) { // Only old sessions
                    if (unlink($file)) {
                        $sessionCleared++;
                    }
                }
            }
            if ($sessionCleared > 0) {
                echo "\033[32m✓\033[0m Old sessions cleared ({$sessionCleared} files)\n";
            }
        }

        // Clear opcache if enabled
        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                echo "\033[32m✓\033[0m OPcache cleared\n";
            }
        }

        echo "\n\033[32mCache cleared successfully!\033[0m\n";

        if ($failed > 0) {
            echo "\033[33mWarning: {$failed} files could not be deleted.\033[0m\n";
        }
    }
}
