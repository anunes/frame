<?php

namespace app\console;

class MakeController
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: Controller name is required.\033[0m\n";
            echo "Usage: php frame make:controller <ControllerName>\n";
            exit(1);
        }

        $name = $arguments[0];

        // Ensure it ends with Controller
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $controllerPath = BASE_PATH . '/app/controllers/' . $name . '.php';

        // Check if controller already exists
        if (file_exists($controllerPath)) {
            echo "\033[31mError: Controller '{$name}' already exists.\033[0m\n";
            exit(1);
        }

        // Generate controller content
        $content = $this->getControllerTemplate($name);

        // Create the controller file
        if (file_put_contents($controllerPath, $content)) {
            echo "\033[32mController created successfully:\033[0m {$controllerPath}\n";
        } else {
            echo "\033[31mError: Failed to create controller.\033[0m\n";
            exit(1);
        }
    }

    /**
     * Get controller template
     */
    private function getControllerTemplate(string $name): string
    {
        return <<<PHP
<?php

namespace app\\controllers;

class {$name} extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(): void
    {
        view('view-name', ['title' => 'Title']);
    }

    /**
     * Show the form for creating a new resource
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource
     */
    public function store(): void
    {
        //
    }

    /**
     * Display the specified resource
     */
    public function show(string \$id): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(string \$id): void
    {
        //
    }

    /**
     * Update the specified resource
     */
    public function update(string \$id): void
    {
        //
    }

    /**
     * Remove the specified resource
     */
    public function destroy(string \$id): void
    {
        //
    }
}

PHP;
    }
}
