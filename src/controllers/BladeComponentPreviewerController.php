<?php

namespace H1ch4m\BladeComponentPreviewer\controllers;

use Illuminate\Contracts\View\View;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class BladeComponentPreviewerController
{

    public function __invoke(): View
    {
        $stylePath = __DIR__ . '/../public/style/blade-component-previewer.css';
        $jsPath = __DIR__ . '/../public/js/blade-component-previewer.js';

        $namespace = 'App\\View\\Components\\';
        $baseDir = app_path('View/Components'); // Laravel app folder

        $components = [];

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));


        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Remove the baseDir part from the full path
                $relativePath = str_replace([$baseDir . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());

                // Normalize slashes for namespaces
                $relativePath = str_replace(['/', '\\'], '\\', $relativePath);

                // Final class
                $class = $namespace . $relativePath;

                if (class_exists($class)) {
                    $components[class_basename($class)] = [
                        'full-class' => $class,
                        'props' => $this->getComponentProps($class),
                        'blade' => $this->getComponentBlade($class)
                    ];
                }
            }
        }

        return view('h1ch4m::blade-component-previewer.index', compact('stylePath', 'jsPath', 'components'));
    }


    /**
     * Extracts constructor parameters (props) from a Blade component class.
     *
     * This method uses PHP Reflection to analyze the component's constructor
     * and returns metadata about each parameter:
     *  - type(s): All declared types (including union & nullable types)
     *  - required: Whether the parameter is required (no default value and not nullable)
     *  - default: The default value if available, otherwise null
     *
     * Example return value:
     * [
     *     "type" => [
     *         "type" => ["string"],
     *         "required" => false,
     *         "default" => "button"
     *     ],
     *     "color" => [
     *         "type" => ["string"],
     *         "required" => false,
     *         "default" => "blue"
     *     ],
     *     "tags" => [
     *         "type" => ["string"],
     *         "required" => true,
     *         "default" => null
     *     ]
     * ]
     *
     * @param  string $class Fully-qualified class name of the component
     * @return array<string, array{
     *     type: string[], 
     *     required: bool, 
     *     default: mixed, 
     *     variadic: bool
     * }>
     */
    private function getComponentProps(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        $props = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();

                $props[$param->getName()] = [
                    'type' => $type ? $type->getName() : 'mixed',
                    'required' => !$param->isDefaultValueAvailable() && !$param->allowsNull(),
                    'default' => $param->isDefaultValueAvailable()
                        ? $param->getDefaultValue()
                        : null,
                ];
            }
        }

        return $props;
    }


    /**
     * Retrieve the Blade template contents associated with a given component class.
     *
     * This method uses reflection to instantiate the component class and call its
     * `render()` method in order to determine which Blade view is associated with it.
     *
     * Supported return types from `render()`:
     *  - \Illuminate\View\View → The method resolves the view path and reads the file contents.
     *  - string (view name, e.g. "components.button") → The method resolves the corresponding
     *    Blade file path (e.g. `resources/views/components/button.blade.php`) and reads it.
     *  - any other return type → Returns null.
     *
     * Example:
     * For a component `App\View\Components\Button` with:
     * ```php
     * public function render()
     * {
     *     return view('components.button');
     * }
     * ```
     * This method will return the contents of:
     * `resources/views/components/button.blade.php`.
     *
     * @param  string  $class  Fully-qualified class name of the component
     * @return string|null     Blade template contents, or null if not found
     */
    private function getComponentBlade(string $class): ?string
    {
        $reflection = new \ReflectionClass($class);

        // Instantiate to call render()
        $instance = $reflection->newInstanceWithoutConstructor();

        if (!method_exists($instance, 'render')) {
            return null;
        }

        $render = $instance->render();

        // Resolve view path
        if ($render instanceof \Illuminate\View\View) {
            $path = $render->getPath();
            return file_exists($path) ? file_get_contents($path) : null;
        }

        if (is_string($render)) {
            // "components.button" → file path
            $viewPath = resource_path('views/' . str_replace('.', '/', $render) . '.blade.php');
            return file_exists($viewPath) ? file_get_contents($viewPath) : null;
        }

        return null;
    }
}
