<?php

namespace H1ch4m\BladeComponentPreviewer\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use H1ch4m\BladeComponentPreviewer\Managers\ComponentManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class BCPController extends Controller
{
    protected ComponentManager $manager;

    public function __construct(ComponentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Show the single-page component editor
     */
    public function index()
    {
        $components = $this->manager->all();

        // Format components for front-end
        $formatted = [];
        foreach ($components as $comp) {
            $formatted[] = [
                'name' => $comp['name'],
                'blade' => File::exists($comp['bladePath']) ? File::get($comp['bladePath']) : '',
                'css' => File::exists($comp['cssPath']) ? File::get($comp['cssPath'])   : '',
                'js' => File::exists($comp['jsPath']) ? File::get($comp['jsPath'])    : '',
                'props' => $comp['props'] ?? [],
            ];
        }

        return view('h1ch4m::blade-component-previewer.index', [
            'components' => $formatted
        ]);
    }

    /**
     * Live preview AJAX endpoint
     */
    public function preview(Request $request, string $name)
    {
        try {
            $component = $this->manager->get($name);

            // Use submitted Blade/CSS/JS or fallback to stored files
            $bladeContent = $request->blade ?? '';
            if (empty($bladeContent) && isset($component['bladePath'])) {
                $bladeContent = File::get($component['bladePath']);
            }

            $cssContent = $request->css ?? '';
            if (empty($cssContent) && isset($component['cssPath'])) {
                $cssContent = File::get($component['cssPath']);
            }

            $jsContent = $request->js ?? '';
            if (empty($jsContent) && isset($component['jsPath'])) {
                $jsContent = File::get($component['jsPath']);
            }

            // Decode props: use value if set, otherwise default
            $rawProps = json_decode($request->props ?? '[]', true) ?: [];

            $props = collect($rawProps)->mapWithKeys(fn($prop) => [
                // $prop['key'] => $prop['value'] !== '' ? $prop['value'] : ($prop['default'] ?? null),
                $prop['key'] => $prop['default'],
            ])->toArray();

            // Render Blade in-memory (no temporary file)
            $html = Blade::render($bladeContent, $props);

            // Inject CSS and JS
            $html = "<style>\n{$cssContent}\n</style>\n" . $html . "\n<script>\n{$jsContent}\n</script>";

            return response()->json([
                'content' => $html
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 200);
        }
    }


    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|alpha_dash'
        ]);

        $name = Str::studly($request->name);

        // Use ComponentManager to scaffold
        $component = $this->manager->create($name);

        return response()->json([
            'success' => true,
            'component' => [
                'name'  => $component['name'],
                'blade' => File::get($component['bladePath']),
                'css'   => File::get($component['cssPath']),
                'js'    => File::get($component['jsPath']),
                'props' => [],
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|alpha_dash',
            'html' => 'nullable|string',
            'css' => 'nullable|string',
            'js' => 'nullable|string',
            'props' => 'nullable|array',
        ]);

        $status = $this->manager->update($request->name, $request->html, $request->css, $request->js, $request->props);

        $name = Str::studly($request->name);

        return response()->json([
            'success' => $status,
            'message' => "Component {$name} updated successfully."
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'name' => 'required|string|alpha_dash',
        ]);

        $status = $this->manager->delete($request->name);

        $name = Str::studly($request->name);

        return response()->json([
            'success' => $status,
            'message' => "Component {$name} deleted successfully."
        ]);
    }
}
