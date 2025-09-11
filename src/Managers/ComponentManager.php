<?php

namespace H1ch4m\BladeComponentPreviewer\Managers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;

class ComponentManager
{
    protected string $bladePath;
    protected string $cssPath;
    protected string $jsPath;
    protected string $stubsPath;
    protected string $classNamespace;
    protected string $classPath;

    public function __construct()
    {
        $this->bladePath = resource_path('views/components');
        $this->cssPath   = public_path('css/components');
        $this->jsPath    = public_path('js/components');
        $this->stubsPath = __DIR__ . '/../Support/Stubs';

        $this->classNamespace = 'App\\View\\Components\\BladeComponentPreviewer';
        $this->classPath = app_path('View/Components/BladeComponentPreviewer');

        $this->ensureDirectoriesExist();
    }

    protected function ensureDirectoriesExist(): void
    {
        File::ensureDirectoryExists($this->bladePath);
        File::ensureDirectoryExists($this->cssPath);
        File::ensureDirectoryExists($this->jsPath);
        File::ensureDirectoryExists($this->classPath);
    }

    /**
     * Create a new component (Blade + CSS + JS + PHP class)
     */
    public function create(string $className, ?string $bladeContent = null, ?string $cssContent = null, ?string $jsContent = null): array
    {
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));

        $bladeFile = "{$this->bladePath}/{$name}.blade.php";
        $cssFile = "{$this->cssPath}/{$name}.css";
        $jsFile = "{$this->jsPath}/{$name}.js";
        $classFile = "{$this->classPath}/{$className}.php";

        if (File::exists($bladeFile)) {
            throw new Exception("Component '{$name}' already exists.");
        }

        // Create Blade
        $bladeContent = $bladeContent ?? File::get("{$this->stubsPath}/component.blade.stub");
        $bladeContent = str_replace(['{{className}}', '{{name}}'], [$className, $name], $bladeContent);
        File::put($bladeFile, $bladeContent);

        // Create CSS
        $cssContent = $cssContent ?? File::get("{$this->stubsPath}/component.css.stub");
        $cssContent = str_replace(['{{className}}', '{{name}}'], [$className, $name], $cssContent);
        File::put($cssFile, $cssContent);

        // Create JS
        $jsContent = $jsContent ?? File::get("{$this->stubsPath}/component.js.stub");
        $jsContent = str_replace(['{{className}}', '{{name}}'], [$className, $name], $jsContent);
        File::put($jsFile, $jsContent);

        // Create PHP class
        $classStub = File::get("{$this->stubsPath}/component.class.stub");
        $classStub = str_replace(['{{className}}', '{{name}}'], [$className, $name], $classStub);
        File::put($classFile, $classStub);


        return [
            'name' => $name,
            'bladePath' => $bladeFile,
            'cssPath' => $cssFile,
            'jsPath' => $jsFile,
        ];
    }

    /**
     * Update component (Blade, CSS, JS)
     */
    public function update(string $name, string $bladeContent, string $cssContent, string $jsContent, array $props): bool
    {
        $paths = $this->getComponentPaths($name);

        // Write updated content
        if (isset($bladeContent)) {
            File::put($paths['bladePath'], $bladeContent);
        }
        if (isset($cssContent)) {
            File::put($paths['cssPath'], $cssContent);
        }
        if (isset($jsContent)) {
            File::put($paths['jsPath'], $jsContent);
        }

        // --- Update PHP Class constructor with props ---
        if ($props) {
            $classFile = $paths['classPath'];

            if (File::exists($classFile)) {
                $classContent = File::get($classFile);

                // Build new constructor signature
                $propStrings = [];
                foreach ($props as $prop) {
                    $type = $prop['type'] ?? 'mixed';
                    $nullable = $prop['nullable'] ?? false;
                    $default = array_key_exists('default', $prop) ? $prop['default'] : null;

                    $typeDecl = $type !== 'mixed' ? ($nullable ? "?$type" : $type) : '';
                    $defaultDecl = $default !== null
                        ? ' = ' . var_export($default, true)
                        : '';

                    $propStrings[] = "public {$typeDecl} \${$prop['key']}{$defaultDecl}";
                }

                $constructor = "public function __construct(" . implode(', ', $propStrings) . ")\n    {\n        //\n    }";

                // Replace old constructor (rudimentary regex)
                if (preg_match('/public function __construct.*?\{.*?\}/s', $classContent)) {
                    $classContent = preg_replace(
                        '/public function __construct.*?\{.*?\}/s',
                        $constructor,
                        $classContent
                    );
                } else {
                    // If no constructor exists, insert before last "}"
                    $classContent = preg_replace(
                        '/}\s*$/',
                        "    {$constructor}\n}\n",
                        $classContent
                    );
                }

                File::put($classFile, $classContent);
            }
        }

        return true;
    }

    /**
     * Delete component (Blade + CSS + JS + class)
     */
    public function delete(string $name): bool
    {
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));

        $bladeFile = "{$this->bladePath}/{$name}.blade.php";
        $cssFile = "{$this->cssPath}/{$name}.css";
        $jsFile = "{$this->jsPath}/{$name}.js";
        $classFile = "{$this->classPath}/{$className}.php";

        if (File::exists($bladeFile)) {
            File::delete($bladeFile);
        }
        if (File::exists($cssFile)) {
            File::delete($cssFile);
        }
        if (File::exists($jsFile)) {
            File::delete($jsFile);
        }
        if (File::exists($classFile)) {
            File::delete($classFile);
        }

        return true;
    }

    /**
     * Get all components for UI listing
     */
    public function all(): array
    {
        $components = [];

        foreach (File::files($this->bladePath) as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $name = explode('.', $name)[0];
            $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));

            $components[] = [
                'name'       => $name,
                'bladePath'  => $file->getPathname(),
                'cssPath'    => "{$this->cssPath}/{$name}.css",
                'jsPath'     => "{$this->jsPath}/{$name}.js",
                'classPath'  => "{$this->classPath}/{$className}.php",
                'props'      => $this->getPropsFromClass("{$this->classNamespace}\\$className"),
                'created_at' => $file->getCTime(),
                'updated_at' => $file->getMTime(),
            ];
        }

        return $components;
    }

    public function getPropsFromClass(string $className): array
    {
        if (!class_exists($className)) {
            return [];
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return [];
        }

        $props = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();
            $default = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;

            $props[$name] = [
                'value' => $default === null ? '' : $default,
                'default' => $default,
                'type' => $type instanceof ReflectionNamedType ? $type->getName() : 'mixed',
                'nullable' => $type?->allowsNull() ?? true,
            ];
        }

        return $props;
    }

    /**
     * Get a single component content
     */
    public function get(string $name): array
    {
        // $name = Str::slug($name, '_');
        $className = ucfirst($name);

        $bladeFile = "{$this->bladePath}/{$name}.blade.php";
        $cssFile   = "{$this->cssPath}/{$name}.css";
        $jsFile    = "{$this->jsPath}/{$name}.js";
        $classFile = "{$this->classPath}/{$className}.php";

        if (!File::exists($bladeFile)) {
            throw new Exception("Component '{$name}' does not exist.");
        }

        return [
            'name' => $name,
            'bladePath' => File::get($bladeFile),
            'cssPath' => File::exists($cssFile) ? File::get($cssFile) : '',
            'jsPath' => File::exists($jsFile) ? File::get($jsFile) : '',
            'classPath' => File::exists($classFile) ? File::get($classFile) : '',
        ];
    }

    /**
     * Get file paths for a given component name
     */
    public function getComponentPaths(string $name): array
    {
        $studly = Str::studly($name);

        return [
            'bladePath' => "{$this->bladePath}/{$name}.blade.php",
            'cssPath'   => "{$this->cssPath}/{$name}.css",
            'jsPath'    => "{$this->jsPath}/{$name}.js",
            'classPath' => "{$this->classPath}/{$studly}.php",
            'className' => $studly,
            'name'      => $name,
        ];
    }
}
