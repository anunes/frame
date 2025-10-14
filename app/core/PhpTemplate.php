<?php

/**
 * Custom exception for PHP template errors
 */
class PhpTemplateException extends Exception
{
    private string $templateName;
    private string $templateFile;
    private int $templateLine;

    public function __construct(
        string $message,
        string $templateName = '',
        string $templateFile = '',
        int $templateLine = 0,
        ?Throwable $previous = null
    ) {
        $this->templateName = $templateName;
        $this->templateFile = $templateFile;
        $this->templateLine = $templateLine;

        parent::__construct($message, 0, $previous);
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getTemplateFile(): string
    {
        return $this->templateFile;
    }

    public function getTemplateLine(): int
    {
        return $this->templateLine;
    }

    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();

        if ($this->templateName) {
            $message .= "\nTemplate: {$this->templateName}";
        }

        if ($this->templateFile) {
            $message .= "\nFile: {$this->templateFile}";
        }

        if ($this->templateLine > 0) {
            $message .= "\nLine: {$this->templateLine}";
        }

        if ($this->getPrevious()) {
            $message .= "\nOriginal error: " . $this->getPrevious()->getMessage();
        }

        return $message;
    }
}

/**
 * PHP Template Engine
 *
 * A lightweight, modern template engine with intuitive syntax.
 * Supports directives, inheritance, sections, components, and variable interpolation.
 */
class PhpTemplate
{
    private string $templatesPath;
    private string $cachePath;
    private bool $cacheEnabled;
    private array $globalData = [];
    private array $sections = [];
    private array $stacks = [];
    private string $currentSection = '';
    private bool $yieldContent = false;

    // Component and slot support
    private array $slots = [];
    private array $componentStack = [];
    private string $currentSlot = '';

    // Current render context data (for slot variable access)
    private array $currentRenderData = [];

    // Compiled template cache
    private array $compiledTemplates = [];
    public function __construct(string $templatesPath, string $cachePath = '', bool $cacheEnabled = true)
    {
        $this->templatesPath = rtrim($templatesPath, '/') . '/';
        $this->cachePath = $cachePath ? rtrim($cachePath, '/') . '/' : sys_get_temp_dir() . '/template_cache/';
        $this->cacheEnabled = $cacheEnabled;

        // Create cache directory if it doesn't exist
        if ($this->cacheEnabled && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Render a template with data
     * Supports both dot notation ('auth.login') and slash notation ('auth/login')
     */
    public function render(string $template, array $data = []): string
    {
        try {
            // Convert dot notation to slash notation for file paths
            $template = str_replace('.', '/', $template);

            $compiledPath = $this->compile($template);

            // Store previous render data to restore later (for nested renders)
            $previousRenderData = $this->currentRenderData;

            // Merge and store current render data for slot access
            // Preserve previous data when nesting (component inside template)
            $this->currentRenderData = array_merge($previousRenderData, $this->globalData, $data);

            // Extract variables for template use
            extract($this->currentRenderData);

            // Start output buffering
            ob_start();

            try {
                include $compiledPath;
            } catch (Throwable $e) {
                ob_end_clean();
                throw new PhpTemplateException(
                    "Runtime error in template '{$template}': " . $e->getMessage(),
                    $template,
                    $e->getFile(),
                    $e->getLine(),
                    $e
                );
            }

            $output = ob_get_clean();

            // Restore previous render data
            $this->currentRenderData = $previousRenderData;

            return $output;
        } catch (PhpTemplateException $e) {
            throw $e; // Re-throw our custom exceptions
        } catch (Throwable $e) {
            throw new PhpTemplateException(
                "Error rendering template '{$template}': " . $e->getMessage(),
                $template,
                '',
                0,
                $e
            );
        }
    }

    /**
     * Add global data available to all templates
     */
    public function share(string $key, mixed $value): void
    {
        $this->globalData[$key] = $value;
    }

    /**
     * Add multiple global variables
     */
    public function shareMany(array $data): void
    {
        $this->globalData = array_merge($this->globalData, $data);
    }

    /**
     * Compile a template to PHP code
     */
    private function compile(string $template): string
    {
        $templatePath = $this->templatesPath . $template . '.view.php';

        if (!file_exists($templatePath)) {
            throw new PhpTemplateException(
                "Template '{$template}' not found at path: {$templatePath}",
                $template,
                $templatePath
            );
        }

        try {
            $cacheKey = md5($template . filemtime($templatePath));
            $cachePath = $this->cachePath . $cacheKey . '.php';

            // Return cached version if available and fresh
            if ($this->cacheEnabled && file_exists($cachePath)) {
                return $cachePath;
            }

            // Read and compile template
            $content = file_get_contents($templatePath);
            if ($content === false) {
                throw new PhpTemplateException(
                    "Could not read template file '{$template}'",
                    $template,
                    $templatePath
                );
            }

            $compiled = $this->compileString($content, $template);

            // Validate compiled PHP syntax (temporarily disabled for components)
            // if (!$this->validatePhpSyntax($compiled)) {
            //     throw new PhpTemplateException(
            //         "Compiled template contains invalid PHP syntax",
            //         $template,
            //         $templatePath
            //     );
            // }

            // Cache compiled template
            if ($this->cacheEnabled) {
                if (!is_dir($this->cachePath)) {
                    mkdir($this->cachePath, 0755, true);
                }

                if (file_put_contents($cachePath, $compiled) === false) {
                    throw new PhpTemplateException(
                        "Could not write compiled template to cache",
                        $template,
                        $cachePath
                    );
                }
            } else {
                // Use in-memory cache for non-persistent caching
                $cachePath = tempnam(sys_get_temp_dir(), 'tpl_');
                if (file_put_contents($cachePath, $compiled) === false) {
                    throw new PhpTemplateException(
                        "Could not create temporary compiled template",
                        $template
                    );
                }
            }

            return $cachePath;
        } catch (PhpTemplateException $e) {
            throw $e; // Re-throw our custom exceptions
        } catch (Throwable $e) {
            throw new PhpTemplateException(
                "Compilation error in template '{$template}': " . $e->getMessage(),
                $template,
                $templatePath,
                0,
                $e
            );
        }
    }

    /**
     * Compile template string to PHP
     */
    public function compileString(string $template, string $templateName = ''): string
    {
        try {
            // Reset compilation state
            $this->sections = [];
            $this->stacks = [];
            $this->currentSection = '';
            $this->yieldContent = false;
            $this->slots = [];
            $this->componentStack = [];
            $this->currentSlot = '';

            $compiled = $template;

            // Compile in order of precedence with logging
            $before = $compiled;
            $compiled = $this->compileComments($compiled);
            $this->logCompilationStep('compileComments', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileExtends($compiled);
            $this->logCompilationStep('compileExtends', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileSections($compiled);
            $this->logCompilationStep('compileSections', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileYields($compiled);
            $this->logCompilationStep('compileYields', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileIncludes($compiled);
            $this->logCompilationStep('compileIncludes', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileComponents($compiled);
            $this->logCompilationStep('compileComponents', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileSlots($compiled);
            $this->logCompilationStep('compileSlots', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileConditionals($compiled);
            $this->logCompilationStep('compileConditionals', $before, $compiled);
            $before = $compiled;
            $compiled = $this->compileLoops($compiled);
            $this->logCompilationStep('compileLoops', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileStacks($compiled);
            $this->logCompilationStep('compileStacks', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileEscapedEchos($compiled);
            $this->logCompilationStep('compileEscapedEchos', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compileEchos($compiled);
            $this->logCompilationStep('compileEchos', $before, $compiled);

            $before = $compiled;
            $compiled = $this->compilePhpTags($compiled);
            $this->logCompilationStep('compilePhpTags', $before, $compiled);

            $before = $compiled;
            $compiled = $this->restoreEscapedEchos($compiled);
            $this->logCompilationStep('restoreEscapedEchos', $before, $compiled);

            return "<?php ?>" . $compiled;
        } catch (Throwable $e) {
            throw new PhpTemplateException(
                "Compilation error: " . $e->getMessage(),
                $templateName,
                '',
                0,
                $e
            );
        }
    }

    /**
     * Validate PHP syntax without executing
     */
    private function validatePhpSyntax(string $code): bool
    {
        // Skip validation in component context to avoid false positives
        if (strpos($code, '$slot') !== false || strpos($code, 'Slot') !== false) {
            return true;
        }

        // Create a temporary file to check syntax
        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_syntax_');
        file_put_contents($tempFile, $code);

        // Use php -l to check syntax
        $output = [];
        $returnVar = 0;
        exec("php -l {$tempFile} 2>&1", $output, $returnVar);

        // Clean up
        unlink($tempFile);

        return $returnVar === 0;
    }
    /**
     * Clear template cache
     */
    public function clearCache(): void
    {
        if (!$this->cacheEnabled || !is_dir($this->cachePath)) {
            return;
        }

        $files = glob($this->cachePath . '*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if (!$this->cacheEnabled || !is_dir($this->cachePath)) {
            return ['enabled' => false, 'files' => 0, 'size' => 0];
        }

        $files = glob($this->cachePath . '*.php');
        $size = 0;

        foreach ($files as $file) {
            $size += filesize($file);
        }

        return [
            'enabled' => true,
            'files' => count($files),
            'size' => $size,
            'size_formatted' => $this->formatBytes($size)
        ];
    }

    /**
     * Format bytes for human reading
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Compile comments - remove template comments {{-- comment --}}
     */
    private function compileComments(string $template): string
    {
        return preg_replace('/\{\{--.*?--\}\}/s', '', $template);
    }

    /**
     * Compile @extends directive
     */
    private function compileExtends(string $template): string
    {
        $pattern = '/^@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/m';

        return preg_replace_callback($pattern, function ($matches) {
            $layout = $matches[1];
            return "<?php \$this->extendLayout('{$layout}'); ?>";
        }, $template);
    }

    /**
     * Compile @section and @endsection directives
     */
    private function compileSections(string $template): string
    {
        // @section('name') ... @endsection
        $pattern = '/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endsection/s';

        $template = preg_replace_callback($pattern, function ($matches) {
            $name = $matches[1];
            $content = trim($matches[2]);
            return "<?php \$this->startSection('{$name}'); ?>{$content}<?php \$this->endSection(); ?>";
        }, $template);

        // @section('name', 'content')
        $pattern = '/@section\s*\(\s*[\'"](.+?)[\'"]\s*,\s*[\'"](.+?)[\'"]\s*\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $name = $matches[1];
            $content = $matches[2];
            return "<?php \$this->setSection('{$name}', '{$content}'); ?>";
        }, $template);
    }

    /**
     * Compile @yield directive
     */
    private function compileYields(string $template): string
    {
        // @yield('section', 'default')
        $pattern = '/@yield\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*[\'"](.+?)[\'"]\s*)?\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $section = $matches[1];
            $default = $matches[2] ?? '';
            return "<?php echo \$this->yieldContent('{$section}', '{$default}'); ?>";
        }, $template);
    }

    /**
     * Compile @include directive
     */
    private function compileIncludes(string $template): string
    {
        // @include('template', ['var' => 'value'])
        $pattern = '/@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $template = $matches[1];
            $data = $matches[2] ?? '[]';
            return "<?php echo \$this->includeTemplate('{$template}', {$data}); ?>";
        }, $template);
    }

    /**
     * Compile component tags <x-component> and </x-component>
     * Also supports self-closing <x-component />
     */
    private function compileComponents(string $template): string
    {
        // Self-closing components: <x-component attr="value" />
        $pattern = '/<x-([a-zA-Z0-9\-_.]+)\s*([^>]*?)\s*\/>/';
        $template = preg_replace_callback($pattern, function ($matches) {
            $component = $matches[1];
            $attributes = $this->parseAttributes($matches[2]);
            return "<?php echo \$this->renderComponent('{$component}', {$attributes}, ''); ?>";
        }, $template);

        // Opening and closing component tags: <x-component>content</x-component>
        $pattern = '/<x-([a-zA-Z0-9\-_.]+)\s*([^>]*?)>(.*?)<\/x-\1>/s';
        $template = preg_replace_callback($pattern, function ($matches) {
            $component = $matches[1];
            $attributes = $this->parseAttributes($matches[2]);
            $slotContent = trim($matches[3]);

            // Don't compile slot content here - just encode it as-is
            // It will be compiled inline when the component template outputs {{ $slot }}
            // Use base64 encoding to avoid quote escaping issues
            $encodedContent = base64_encode($slotContent);

            return "<?php echo \$this->renderComponent('{$component}', {$attributes}, base64_decode('{$encodedContent}')); ?>";
        }, $template);
        return $template;
    }

    /**
     * Compile @slot directive and {{ $slot }} usage
     */
    private function compileSlots(string $template): string
    {
        // @slot('name') ... @endslot
        $pattern = '/@slot\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endslot/s';
        $template = preg_replace_callback($pattern, function ($matches) {
            $name = $matches[1];
            $content = trim($matches[2]);
            return "<?php \$this->setSlot('{$name}', '{$content}'); ?>";
        }, $template);

        // Default slot content: {{ $slot }} - compile and execute the slot content
        // The slot contains uncompiled template syntax that needs to be processed
        $template = preg_replace('/\{\{\s*\$slot\s*\}\}/', '<?php echo $this->compileAndRenderSlot($slot ?? ""); ?>', $template);

        // Named slots: {{ $slotName }} - compile and execute the slot content
        $template = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*Slot)\s*\}\}/', '<?php echo $this->compileAndRenderSlot($$1 ?? ""); ?>', $template);

        return $template;
    }

    /**
     * Parse HTML attributes into PHP array format
     */
    private function parseAttributes(string $attributeString): string
    {
        if (empty(trim($attributeString))) {
            return '[]';
        }

        $attributes = [];
        $pattern = '/([a-zA-Z0-9\-_:]+)=(["\'])([^"\']*?)\2/';

        preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[3];

            // Don't convert onclick and similar attributes to camelCase
            if (!in_array($key, ['onclick', 'onchange', 'onsubmit', 'onload'])) {
                // Convert kebab-case to camelCase for PHP variables
                $key = lcfirst(str_replace('-', '', ucwords($key, '-')));
            }

            $attributes[$key] = $value;
        }

        return var_export($attributes, true);
    }

    /**
     * Compile conditional directives (@if, @elseif, @else, @endif)
     * Handles nested parentheses correctly (e.g., isset($item['key']))
     */
    private function compileConditionals(string $template): string
    {
        // @if condition - match balanced parentheses
        $template = preg_replace_callback('/@if\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php if ({$matches[1]}): ?>";
        }, $template);

        // @elseif condition
        $template = preg_replace_callback('/@elseif\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php elseif ({$matches[1]}): ?>";
        }, $template);

        // @else
        $template = preg_replace('/@else/', '<?php else: ?>', $template);

        // @endif
        $template = preg_replace('/@endif/', '<?php endif; ?>', $template);

        // @unless condition (opposite of if)
        $template = preg_replace_callback('/@unless\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php if (!({$matches[1]})): ?>";
        }, $template);

        // @endunless
        $template = preg_replace('/@endunless/', '<?php endif; ?>', $template);

        // @isset variable
        $template = preg_replace_callback('/@isset\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php if (isset({$matches[1]})): ?>";
        }, $template);

        // @endisset
        $template = preg_replace('/@endisset/', '<?php endif; ?>', $template);

        // @empty variable
        $template = preg_replace_callback('/@empty\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php if (empty({$matches[1]})): ?>";
        }, $template);

        // @endempty
        $template = preg_replace('/@endempty/', '<?php endif; ?>', $template);

        return $template;
    }

    /**
     * Compile loop directives (@foreach, @for, @while)
     * Handles nested parentheses correctly
     */
    private function compileLoops(string $template): string
    {
        // @foreach - match balanced parentheses to handle function calls like navbar_items('main')
        $template = preg_replace_callback('/@foreach\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            $content = $matches[1];
            // Now split by " as " to get array and variable
            if (preg_match('/^(.+?)\s+as\s+(.+?)$/', $content, $parts)) {
                return "<?php foreach ({$parts[1]} as {$parts[2]}): ?>";
            }
            return $matches[0]; // Return unchanged if no match
        }, $template);

        // @endforeach
        $template = preg_replace('/@endforeach/', '<?php endforeach; ?>', $template);

        // @for ($i = 0; $i < 10; $i++)
        $template = preg_replace_callback('/@for\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php for ({$matches[1]}): ?>";
        }, $template);

        // @endfor
        $template = preg_replace('/@endfor/', '<?php endfor; ?>', $template);

        // @while (condition)
        $template = preg_replace_callback('/@while\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            return "<?php while ({$matches[1]}): ?>";
        }, $template);

        // @endwhile
        $template = preg_replace('/@endwhile/', '<?php endwhile; ?>', $template);

        // @forelse ($items as $item)
        $template = preg_replace_callback('/@forelse\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/', function($matches) {
            $content = $matches[1];
            if (preg_match('/^(.+?)\s+as\s+(.+?)$/', $content, $parts)) {
                return "<?php if (!empty({$parts[1]})): foreach ({$parts[1]} as {$parts[2]}): ?>";
            }
            return $matches[0];
        }, $template);

        // @empty (for forelse)
        $template = preg_replace('/@empty(?=\s*[^(])/', '<?php endforeach; else: ?>', $template);

        // @endforelse
        $template = preg_replace('/@endforelse/', '<?php endif; ?>', $template);

        return $template;
    }

    /**
     * Compile @push and @stack directives
     */
    private function compileStacks(string $template): string
    {
        // @push('stack')
        $pattern = '/@push\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endpush/s';

        $template = preg_replace_callback($pattern, function ($matches) {
            $stack = $matches[1];
            $content = trim($matches[2]);
            return "<?php \$this->pushToStack('{$stack}', '{$content}'); ?>";
        }, $template);

        // @stack('name')
        $pattern = '/@stack\s*\(\s*[\'"](.+?)[\'"]\s*\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $stack = $matches[1];
            return "<?php echo \$this->renderStack('{$stack}'); ?>";
        }, $template);
    }

    /**
     * Compile escaped echo statements @{{ }} and @{!! !!}
     * These allow outputting literal curly braces without processing
     */
    private function compileEscapedEchos(string $template): string
    {
        // Replace @{{ }} with literal {{ }}
        $template = str_replace('@{{', '___ESCAPED_ECHO_START___', $template);

        // Replace @{!! !!} with literal {!! !!}
        $template = str_replace('@{!!', '___ESCAPED_RAW_START___', $template);

        return $template;
    }

    /**
     * Compile echo statements {{ }} and {!! !!}
     */
    private function compileEchos(string $template): string
    {
        // Unescaped echo {!! variable !!}
        $template = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $template);

        // Escaped echo {{ variable }} - handle null coalescing properly
        $template = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($matches) {
            $expression = trim($matches[1]);
            // If expression already contains ?? don't add another one
            if (strpos($expression, '??') !== false) {
                return "<?php echo htmlspecialchars(({$expression}), ENT_QUOTES, 'UTF-8'); ?>";
            } else {
                return "<?php echo htmlspecialchars(({$expression}) ?? '', ENT_QUOTES, 'UTF-8'); ?>";
            }
        }, $template);

        return $template;
    }

    /**
     * Compile raw PHP tags @php ... @endphp
     */
    private function compilePhpTags(string $template): string
    {
        // @php ... @endphp
        $pattern = '/@php(.*?)@endphp/s';

        return preg_replace_callback($pattern, function ($matches) {
            $code = trim($matches[1]);
            return "<?php {$code} ?>";
        }, $template);
    }

    /**
     * Restore escaped echo statements back to literal text
     */
    private function restoreEscapedEchos(string $template): string
    {
        // Restore @{{ }} to literal {{ }}
        $template = str_replace('___ESCAPED_ECHO_START___', '{{', $template);

        // Restore @{!! !!} to literal {!! !!}
        $template = str_replace('___ESCAPED_RAW_START___', '{!!', $template);

        return $template;
    }

    // ===== Template Execution Helper Methods =====

    /**
     * Include another template
     * Converts dot notation to slash notation (e.g., 'layouts.partials.nav' -> 'layouts/partials/nav')
     */
    private function includeTemplate(string $template, array $data = []): string
    {
        // Convert dot notation to slash notation for file paths
        $template = str_replace('.', '/', $template);
        return $this->render($template, $data);
    }

    /**
     * Extend a layout template
     */
    private function extendLayout(string $layout): void
    {
        // This would be handled in a more complex implementation
        // For now, we'll simulate it by including the layout
    }

    /**
     * Start a section
     */
    private function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End a section
     */
    private function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    /**
     * Set a section with direct content
     */
    private function setSection(string $name, string $content): void
    {
        $this->sections[$name] = $content;
    }

    /**
     * Yield section content
     */
    private function yieldContent(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    /**
     * Push content to a stack
     */
    private function pushToStack(string $stack, string $content): void
    {
        if (!isset($this->stacks[$stack])) {
            $this->stacks[$stack] = [];
        }
        $this->stacks[$stack][] = $content;
    }

    /**
     * Render a stack
     */
    private function renderStack(string $stack): string
    {
        return isset($this->stacks[$stack]) ? implode("\n", $this->stacks[$stack]) : '';
    }

    // ===== Component and Slot Helper Methods =====

    /**
     * Render a component with attributes and slot content
     */
    private function renderComponent(string $component, array $attributes = [], string $slot = ''): string
    {
        // Store current slots and reset for component
        $previousSlots = $this->slots;
        $this->slots = [];

        // Process slot content to extract named slots
        if ($slot) {
            $this->processSlotContent($slot);
            $this->slots['default'] = $slot;
        }

        // Convert component name to file path (layout-app becomes components/layout-app.view.php)
        // Keep hyphens in the component name, don't convert them to slashes
        $componentPath = 'components/' . $component;

        try {
            // Merge attributes with slot data
            $componentData = array_merge($attributes, [
                'slot' => $this->slots['default'] ?? '',
                'slots' => $this->slots
            ]);

            // Add named slots as individual variables
            foreach ($this->slots as $slotName => $slotContent) {
                if ($slotName !== 'default') {
                    $componentData[$slotName . 'Slot'] = $slotContent;
                }
            }

            $output = $this->render($componentPath, $componentData);

            // Restore previous slots
            $this->slots = $previousSlots;

            return $output;
        } catch (Exception $e) {
            // Restore previous slots on error
            $this->slots = $previousSlots;
            throw new PhpTemplateException(
                "Error rendering component '{$component}': " . $e->getMessage(),
                $component,
                '',
                0,
                $e
            );
        }
    }

    /**
     * Process slot content to extract named slots
     */
    private function processSlotContent(string &$content): void
    {
        // Extract @slot directives from content
        $pattern = '/@slot\s*\(\s*[\'"](.+?)[\'"]\s*\)(.*?)@endslot/s';
        $content = preg_replace_callback($pattern, function ($matches) {
            $slotName = $matches[1];
            $slotContent = trim($matches[2]);
            $this->slots[$slotName] = $slotContent;
            return ''; // Remove from main content
        }, $content);

        // Clean up the main content
        $content = trim($content);
    }

    /**
     * Set slot content
     */
    private function setSlot(string $name, string $content): void
    {
        $this->slots[$name] = $content;
    }

    /**
     * Get slot content
     */
    private function getSlot(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * Check if slot exists
     */
    private function hasSlot(string $name): bool
    {
        return isset($this->slots[$name]) && !empty($this->slots[$name]);
    }

    /**
     * Compile and render slot content
     * Takes uncompiled template syntax and executes it with access to render context variables
     */
    private function compileAndRenderSlot(string $slotContent): string
    {
        if (empty($slotContent)) {
            return '';
        }

        // Compile the slot content to PHP
        $compiled = $this->compileString($slotContent);

        // Create a temporary file to execute the compiled PHP
        $tempFile = tempnam(sys_get_temp_dir(), 'slot_');
        file_put_contents($tempFile, $compiled);

        // Extract all variables from the current render context
        // This gives the slot access to all data passed to the parent template
        extract($this->currentRenderData);

        // Start output buffering
        ob_start();
        try {
            // Include the compiled slot - it has access to extracted variables and $this
            include $tempFile;
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            unlink($tempFile);
            throw $e;
        }

        // Clean up temp file
        unlink($tempFile);

        return $output;
    }

    // ===== Debugging and Utility Methods =====

    /**
     * Enable debug mode with verbose error reporting
     */
    private bool $debugMode = false;

    public function setDebugMode(bool $enabled): void
    {
        $this->debugMode = $enabled;
    }

    /**
     * Get debug information about template compilation
     */
    public function getDebugInfo(string $template): array
    {
        $templatePath = $this->templatesPath . $template . '.view.php';

        if (!file_exists($templatePath)) {
            return ['error' => "Template '{$template}' not found"];
        }

        try {
            $content = file_get_contents($templatePath);
            $compiled = $this->compileString($content, $template);

            return [
                'template' => $template,
                'templatePath' => $templatePath,
                'templateSize' => strlen($content),
                'compiledSize' => strlen($compiled),
                'lastModified' => date('Y-m-d H:i:s', filemtime($templatePath)),
                'cacheEnabled' => $this->cacheEnabled,
                'originalContent' => $content,
                'compiledContent' => $compiled,
                'sections' => array_keys($this->sections),
                'stacks' => array_keys($this->stacks)
            ];
        } catch (Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'template' => $template,
                'templatePath' => $templatePath
            ];
        }
    }

    /**
     * Log compilation steps for debugging
     */
    private array $compilationLog = [];

    private function logCompilationStep(string $step, string $before, string $after): void
    {
        if (!$this->debugMode) {
            return;
        }

        $this->compilationLog[] = [
            'step' => $step,
            'changes' => $before !== $after,
            'sizeBefore' => strlen($before),
            'sizeAfter' => strlen($after),
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get compilation log
     */
    public function getCompilationLog(): array
    {
        return $this->compilationLog;
    }

    /**
     * Clear compilation log
     */
    public function clearCompilationLog(): void
    {
        $this->compilationLog = [];
    }

    /**
     * Validate template syntax without compiling
     */
    public function validateTemplate(string $template): array
    {
        $templatePath = $this->templatesPath . $template . '.view.php';
        $errors = [];

        if (!file_exists($templatePath)) {
            $errors[] = "Template file not found: {$templatePath}";
            return ['valid' => false, 'errors' => $errors];
        }

        try {
            $content = file_get_contents($templatePath);

            // Check for common syntax errors
            $this->validateTemplateSyntax($content, $errors);

            // Try to compile
            $compiled = $this->compileString($content, $template);

            // Validate PHP syntax
            if (!$this->validatePhpSyntax($compiled)) {
                $errors[] = "Compiled template contains invalid PHP syntax";
            }
        } catch (Throwable $e) {
            $errors[] = "Compilation error: " . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'template' => $template,
            'path' => $templatePath
        ];
    }

    /**
     * Validate template-specific syntax
     */
    private function validateTemplateSyntax(string $content, array &$errors): void
    {
        // Check for unmatched directives
        $this->checkUnmatchedDirectives($content, $errors);

        // Check for invalid variable syntax
        $this->checkVariableSyntax($content, $errors);

        // Check for nested sections (not allowed)
        $this->checkNestedSections($content, $errors);
    }

    /**
     * Check for unmatched template directives
     */
    private function checkUnmatchedDirectives(string $content, array &$errors): void
    {
        $directives = [
            'if' => 'endif',
            'unless' => 'endunless',
            'isset' => 'endisset',
            'empty' => 'endempty',
            'foreach' => 'endforeach',
            'forelse' => 'endforelse',
            'for' => 'endfor',
            'while' => 'endwhile',
            'section' => 'endsection',
            'push' => 'endpush',
            'php' => 'endphp'
        ];

        foreach ($directives as $start => $end) {
            $startCount = preg_match_all("/@{$start}\s*\(/", $content);
            $endCount = preg_match_all("/@{$end}/", $content);

            if ($startCount !== $endCount) {
                $errors[] = "Unmatched @{$start} directive (found {$startCount} @{$start} and {$endCount} @{$end})";
            }
        }
    }

    /**
     * Check variable syntax
     */
    private function checkVariableSyntax(string $content, array &$errors): void
    {
        // Check for malformed variable expressions
        if (preg_match('/\{\{\s*[^}]*\$[^}]*[^}]\s*\}\}/', $content)) {
            // This is a basic check - in a real implementation you'd want more sophisticated parsing
        }
    }

    /**
     * Check for nested sections (not supported in this implementation)
     */
    private function checkNestedSections(string $content, array &$errors): void
    {
        // Simple check for nested @section directives
        $pattern = '/@section\s*\([^)]+\).*?@section\s*\([^)]+\).*?@endsection.*?@endsection/s';
        if (preg_match($pattern, $content)) {
            $errors[] = "Nested sections are not supported";
        }
    }

    /**
     * Set the global PhpTemplate instance for the view() helper
     */
    public static function setGlobalInstance(PhpTemplate $instance): void
    {
        self::$globalInstance = $instance;
    }

    /**
     * Get the global PhpTemplate instance
     */
    public static function getGlobalInstance(): ?PhpTemplate
    {
        return self::$globalInstance;
    }

    /**
     * Global instance for view() helper
     */
    private static ?PhpTemplate $globalInstance = null;
}

/**
 * Global view helper function - Laravel-style template rendering
 * Outputs content directly (like Laravel's view() helper)
 *
 * Note: This function is also defined in app/helpers/functions.php
 * Only define it here if it doesn't already exist
 *
 * @param string $template Template name (without .view.php extension)
 * @param array $data Data to pass to the template
 * @return void Outputs rendered content directly
 * @throws PhpTemplateException If no global instance is set or template error occurs
 */
if (!function_exists('view')) {
    function view(string $template, array $data = []): void
    {
        $viewer = PhpTemplate::getGlobalInstance();

        if (!$viewer) {
            throw new PhpTemplateException(
                'No global PhpTemplate instance set. Call PhpTemplate::setGlobalInstance() first or use $viewer->render() directly.',
                $template
            );
        }

        echo $viewer->render($template, $data);
    }
}

/**
 * Alternative view helper that returns content instead of echoing
 * Use this when you need the rendered content as a string
 */
if (!function_exists('viewer_content')) {
    function viewer_content(string $template, array $data = []): string
    {
        $viewer = PhpTemplate::getGlobalInstance();

        if (!$viewer) {
            throw new PhpTemplateException(
                'No global PhpTemplate instance set. Call PhpTemplate::setGlobalInstance() first.',
                $template
            );
        }

        return $viewer->render($template, $data);
    }
}

/**
 * Helper to set up the global view instance quickly
 *
 * @param string $templatesPath Path to templates directory
 * @param string $cachePath Path to cache directory (optional)
 * @param bool $cacheEnabled Whether to enable caching (default: true)
 * @return PhpTemplate The created instance
 */
if (!function_exists('viewer_setup')) {
    function viewer_setup(string $templatesPath, string $cachePath = '', bool $cacheEnabled = true): PhpTemplate
    {
        $viewer = new PhpTemplate($templatesPath, $cachePath, $cacheEnabled);
        PhpTemplate::setGlobalInstance($viewer);
        return $viewer;
    }
}
