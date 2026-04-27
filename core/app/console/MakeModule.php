<?php

namespace app\console;

class MakeModule
{
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: Module name is required.\033[0m\n";
            echo "Usage: php frame make:module <name> [--start] [--nav] [--nav-label=\"Label\"] [--nav-icon=bi-icon]\n";
            exit(1);
        }

        $module = $this->normalizeModuleName($arguments[0]);
        $isStart = in_array('--start', $arguments, true);
        $addNav = in_array('--nav', $arguments, true);

        $modulePath = BASE_PATH . '/files/' . $module;
        if (is_dir($modulePath)) {
            echo "\033[31mError: Module '{$module}' already exists.\033[0m\n";
            exit(1);
        }

        $classBase = $this->studly($module);
        $folders = ['controllers', 'models', 'views', 'routes'];

        foreach ($folders as $folder) {
            $path = $modulePath . '/' . $folder;
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                echo "\033[31mError: Failed to create directory: {$path}\033[0m\n";
                exit(1);
            }
        }

        $files = [
            $modulePath . '/controllers/' . $classBase . 'Controller.php' => $this->controllerTemplate($module, $classBase),
            $modulePath . '/models/' . $classBase . '.php' => $this->modelTemplate($module, $classBase),
            $modulePath . '/views/index.view.php' => $this->viewTemplate($module, $classBase),
            $modulePath . '/routes/web.php' => $this->routesTemplate($module, $classBase),
        ];

        foreach ($files as $path => $content) {
            if (file_put_contents($path, $content) === false) {
                echo "\033[31mError: Failed to create file: {$path}\033[0m\n";
                exit(1);
            }
        }

        if ($isStart) {
            $this->writeStartModule($module);
        }

        if ($addNav) {
            $this->writeNavbarEntry(
                $module,
                $this->optionValue($arguments, '--nav-label') ?? $this->humanTitle($module),
                $this->optionValue($arguments, '--nav-icon') ?? 'bi-folder'
            );
        }

        echo "\033[32mModule created successfully:\033[0m {$modulePath}\n";
        echo "Routes: /{$module}" . ($isStart ? " and /\n" : "\n");
        echo "View name: {$module}::index\n";
        if ($addNav) {
            echo "Navbar: added {$module} between Home and About\n";
        }
    }

    private function normalizeModuleName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_]+/', '_', $name) ?? '';
        $name = trim($name, '_');

        if ($name === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            echo "\033[31mError: Module name must start with a letter and contain only letters, numbers, or underscores.\033[0m\n";
            exit(1);
        }

        return $name;
    }

    private function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    private function humanTitle(string $name): string
    {
        return ucwords(str_replace('_', ' ', $name));
    }

    private function optionValue(array $arguments, string $option): ?string
    {
        foreach ($arguments as $index => $argument) {
            if (str_starts_with($argument, $option . '=')) {
                $value = trim(substr($argument, strlen($option) + 1));
                return $value === '' ? null : $value;
            }

            if ($argument === $option && isset($arguments[$index + 1])) {
                $value = trim((string) $arguments[$index + 1]);
                return $value === '' || str_starts_with($value, '--') ? null : $value;
            }
        }

        return null;
    }

    private function writeStartModule(string $module): void
    {
        $envPath = BASE_PATH . '/.env';

        if (!is_file($envPath)) {
            file_put_contents($envPath, "APP_START_MODULE={$module}\n", FILE_APPEND);
            return;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            return;
        }

        if (preg_match('/^APP_START_MODULE=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_START_MODULE=.*$/m', 'APP_START_MODULE=' . $module, $contents);
        } else {
            $contents = rtrim($contents) . "\nAPP_START_MODULE={$module}\n";
        }

        file_put_contents($envPath, $contents);
    }

    private function writeNavbarEntry(string $module, string $label, string $icon): void
    {
        $configPath = APP_PATH . '/config/module_nav.php';
        $config = is_file($configPath) ? require $configPath : [];
        $config = is_array($config) ? $config : [];

        $config[$module] = [
            'label' => $label,
            'url' => '/' . $module,
            'icon' => $icon,
            'active' => $module,
        ];

        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * Navbar entries created for user content modules.\n";
        $content .= " *\n";
        $content .= " * These are injected into the main navbar after Home and before About.\n";
        $content .= " */\n";
        $content .= "return " . var_export($config, true) . ";\n";

        if (file_put_contents($configPath, $content) === false) {
            echo "\033[31mError: Failed to update module navbar configuration.\033[0m\n";
            exit(1);
        }
    }

    private function controllerTemplate(string $module, string $classBase): string
    {
        return <<<PHP
<?php

namespace files\\{$module}\\controllers;

use app\\controllers\\Controller;

class {$classBase}Controller extends Controller
{
    public function index(): void
    {
        view('{$module}::index', ['title' => '{$classBase}']);
    }
}

PHP;
    }

    private function modelTemplate(string $module, string $classBase): string
    {
        $table = str_replace('-', '_', $module);

        return <<<PHP
<?php

namespace files\\{$module}\\models;

use app\\core\\Database;

class {$classBase} extends Database
{
    protected string \$table = '{$table}';
}

PHP;
    }

    private function viewTemplate(string $module, string $classBase): string
    {
        return <<<TEMPLATE
<x-layout-app title="{$classBase}">

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">{$classBase}</h2>

        <div class="card shadow">
            <div class="card-body">
                <p>{$classBase} module content goes here.</p>
            </div>
        </div>
    </div>
</div>

</x-layout-app>

TEMPLATE;
    }

    private function routesTemplate(string $module, string $classBase): string
    {
        return <<<PHP
<?php

use app\\core\\ModuleManager;
use files\\{$module}\\controllers\\{$classBase}Controller;

\$router->get('/{$module}', [{$classBase}Controller::class, 'index'])->name('{$module}/index');

if (ModuleManager::isStarting('{$module}')) {
    \$router->get('/', [{$classBase}Controller::class, 'index'])->name('{$module}/start');
}

PHP;
    }
}
