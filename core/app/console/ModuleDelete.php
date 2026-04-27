<?php

namespace app\console;

class ModuleDelete
{
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: Module name is required.\033[0m\n";
            echo "Usage: php frame module:delete <name> --force\n";
            exit(1);
        }

        $module = strtolower(trim($arguments[0]));
        if (!$this->isValidModuleName($module)) {
            echo "\033[31mError: Invalid module name.\033[0m\n";
            exit(1);
        }

        if (!in_array('--force', $arguments, true)) {
            echo "\033[31mError: Deleting a module is permanent. Re-run with --force to confirm.\033[0m\n";
            echo "Usage: php frame module:delete {$module} --force\n";
            exit(1);
        }

        $modulePath = BASE_PATH . '/files/' . $module;
        if (!is_dir($modulePath)) {
            echo "\033[31mError: Module '{$module}' was not found in files/.\033[0m\n";
            exit(1);
        }

        $this->deleteDirectory($modulePath);
        $envChanged = $this->removeModuleFromEnv($module);
        $navChanged = $this->removeModuleFromNavbar($module);
        $this->clearViewCache();

        echo "\033[32mModule deleted successfully:\033[0m {$module}\n";
        if ($envChanged) {
            echo "Updated .env module settings.\n";
        }
        if ($navChanged) {
            echo "Removed module navbar entry.\n";
        }
        echo "Cleared compiled view cache.\n";
    }

    private function isValidModuleName(string $name): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $name);
    }

    private function deleteDirectory(string $directory): void
    {
        $filesRoot = realpath(BASE_PATH . '/files');
        $moduleRoot = realpath($directory);

        if ($filesRoot === false || $moduleRoot === false) {
            echo "\033[31mError: Could not resolve module path.\033[0m\n";
            exit(1);
        }

        $filesRootPrefix = rtrim($filesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($moduleRoot, $filesRootPrefix)) {
            echo "\033[31mError: Refusing to delete outside files/.\033[0m\n";
            exit(1);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                if (!rmdir($path)) {
                    echo "\033[31mError: Failed to remove directory: {$path}\033[0m\n";
                    exit(1);
                }
                continue;
            }

            if (!unlink($path)) {
                echo "\033[31mError: Failed to remove file: {$path}\033[0m\n";
                exit(1);
            }
        }

        if (!rmdir($moduleRoot)) {
            echo "\033[31mError: Failed to remove module directory: {$moduleRoot}\033[0m\n";
            exit(1);
        }
    }

    private function removeModuleFromEnv(string $module): bool
    {
        $envPath = BASE_PATH . '/.env';
        if (!is_file($envPath)) {
            return false;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            echo "\033[31mError: Unable to read .env after deleting module.\033[0m\n";
            exit(1);
        }

        $original = $contents;
        $contents = $this->clearStartModuleIfNeeded($contents, $module);
        $contents = $this->removeEnabledModuleIfNeeded($contents, $module);

        if ($contents === $original) {
            return false;
        }

        if (file_put_contents($envPath, $contents) === false) {
            echo "\033[31mError: Unable to update .env after deleting module.\033[0m\n";
            exit(1);
        }

        return true;
    }

    private function removeModuleFromNavbar(string $module): bool
    {
        $configPath = APP_PATH . '/config/module_nav.php';
        if (!is_file($configPath)) {
            return false;
        }

        $config = require $configPath;
        if (!is_array($config) || !array_key_exists($module, $config)) {
            return false;
        }

        unset($config[$module]);

        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * Navbar entries created for user content modules.\n";
        $content .= " *\n";
        $content .= " * These are injected into the main navbar after Home and before About.\n";
        $content .= " */\n";
        $content .= "return " . var_export($config, true) . ";\n";

        if (file_put_contents($configPath, $content) === false) {
            echo "\033[31mError: Unable to update module navbar configuration after deleting module.\033[0m\n";
            exit(1);
        }

        return true;
    }

    private function clearViewCache(): void
    {
        $cachePath = STORAGE_PATH . '/cache/views';
        if (!is_dir($cachePath)) {
            return;
        }

        foreach (glob($cachePath . '/*.php') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function clearStartModuleIfNeeded(string $contents, string $module): string
    {
        return preg_replace_callback('/^APP_START_MODULE=(.*)$/m', function (array $matches) use ($module) {
            $value = trim($matches[1], " \t\n\r\0\x0B\"'");
            return $value === $module ? 'APP_START_MODULE=' : $matches[0];
        }, $contents) ?? $contents;
    }

    private function removeEnabledModuleIfNeeded(string $contents, string $module): string
    {
        return preg_replace_callback('/^APP_MODULES=(.*)$/m', function (array $matches) use ($module) {
            $rawValue = trim($matches[1]);
            $quote = '';

            if (
                strlen($rawValue) >= 2
                && (($rawValue[0] === '"' && substr($rawValue, -1) === '"')
                    || ($rawValue[0] === "'" && substr($rawValue, -1) === "'"))
            ) {
                $quote = $rawValue[0];
                $rawValue = substr($rawValue, 1, -1);
            }

            $modules = array_values(array_filter(array_map('trim', explode(',', $rawValue))));
            $modules = array_values(array_filter($modules, fn (string $enabled) => $enabled !== $module));

            return 'APP_MODULES=' . $quote . implode(',', $modules) . $quote;
        }, $contents) ?? $contents;
    }
}
