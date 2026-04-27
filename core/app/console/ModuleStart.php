<?php

namespace app\console;

use app\core\ModuleManager;

class ModuleStart
{
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: Module name is required.\033[0m\n";
            echo "Usage: php frame module:start <name|--clear>\n";
            exit(1);
        }

        $module = strtolower(trim($arguments[0]));
        if (in_array($module, ['--clear', 'clear', 'default', 'app'], true)) {
            $this->writeStartModule('');
            echo "\033[32mStarting module cleared.\033[0m Frame will use the original start page.\n";
            return;
        }

        if (!in_array($module, ModuleManager::modules(), true)) {
            echo "\033[31mError: Module '{$module}' was not found in files/.\033[0m\n";
            exit(1);
        }

        $this->writeStartModule($module);

        echo "\033[32mStarting module set:\033[0m {$module}\n";
    }

    private function writeStartModule(string $module): void
    {
        $envPath = BASE_PATH . '/.env';
        $line = 'APP_START_MODULE=' . $module;

        if (!is_file($envPath)) {
            file_put_contents($envPath, $line . "\n", FILE_APPEND);
            return;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            echo "\033[31mError: Unable to read .env.\033[0m\n";
            exit(1);
        }

        if (preg_match('/^APP_START_MODULE=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_START_MODULE=.*$/m', $line, $contents);
        } else {
            $contents = rtrim($contents) . "\n" . $line . "\n";
        }

        if (file_put_contents($envPath, $contents) === false) {
            echo "\033[31mError: Unable to write .env.\033[0m\n";
            exit(1);
        }
    }
}
