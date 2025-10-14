<?php

namespace app\console;

class MakeView
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: View name is required.\033[0m\n";
            echo "Usage: php frame make:view <folder/view-name>\n";
            echo "Example: php frame make:view admin/dashboard\n";
            exit(1);
        }

        $viewName = $arguments[0];
        $viewPath = BASE_PATH . '/app/views/' . $viewName . '.view.php';

        // Create directory if it doesn't exist
        $viewDir = dirname($viewPath);
        if (!is_dir($viewDir)) {
            if (!mkdir($viewDir, 0755, true)) {
                echo "\033[31mError: Failed to create directory: {$viewDir}\033[0m\n";
                exit(1);
            }
        }

        // Check if view already exists
        if (file_exists($viewPath)) {
            echo "\033[31mError: View '{$viewName}' already exists.\033[0m\n";
            exit(1);
        }

        // Generate view content
        $content = $this->getViewTemplate($viewName);

        // Create the view file
        if (file_put_contents($viewPath, $content)) {
            echo "\033[32mView created successfully:\033[0m {$viewPath}\n";
        } else {
            echo "\033[31mError: Failed to create view.\033[0m\n";
            exit(1);
        }
    }

    /**
     * Get view template
     */
    private function getViewTemplate(string $name): string
    {
        $title = ucfirst(basename($name));

        return <<<TEMPLATE
<x-layout-app title="{$title}">

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">{$title}</h2>

        <div class="card shadow">
            <div class="card-body">
                <p>View content goes here.</p>
            </div>
        </div>
    </div>
</div>

</x-layout-app>

TEMPLATE;
    }
}
