<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blade Component Previewer</title>

    <link rel="stylesheet" href="{{ url('blade-component-previewer/public/style/blade-component-previewer.css') }}">

    <script src="{{ url('blade-component-previewer/public/js/ace.js') }}"></script>
    <script src="{{ url('blade-component-previewer/public/js/ext-language_tools.js') }}"></script>

    <script>
        const components = @json($components);
        const keywords = @json($keywords);
        const csrfToken = "{{ csrf_token() }}";
        const createRoute = "{{ route('blade-component-previewer.create') }}";
        const updateRoute = "{{ route('blade-component-previewer.update') }}";
        const deleteRoute = "{{ route('blade-component-previewer.delete') }}";
        const previewRoute = "{{ route('blade-component-previewer.preview', '__NAME__') }}";
    </script>
    <script src="{{ url('blade-component-previewer/public/js/blade-component-previewer.js') }}" defer></script>
</head>

<body>
    <div class="container__zone-1">
        <h3>Components</h3>
        <div id="component-btns">
        </div>

        <h3>Actions</h3>
        <button id="new-component-btn" class="container__button">➕ New Component</button>
        <button id="save-component-btn" class="container__button">💾 Save Component</button>
        <button id="delete-component-btn" class="container__button">🗑️ Delete Component</button>

        <h3>Props</h3>
        <div id="inputs-container"></div>
        <button id="add-prop-btn">Add Prop</button>

        <div class="container__editor-wrap">
            <h3>Blade HTML</h3>
            <textarea id="htmlInput" hidden></textarea>
            <div id="php-editor" style="height: 400px; width: 100%; border: 1px solid #ccc;"></div>

            <h3>CSS</h3>
            <textarea id="cssInput" hidden></textarea>
            <div id="css-editor" style="height: 400px; width: 100%; border: 1px solid #ccc;"></div>

            <h3>JS</h3>
            <textarea id="jsInput" hidden></textarea>
            <div id="js-editor" style="height: 400px; width: 100%; border: 1px solid #ccc;"></div>
        </div>
    </div>

    <div class="container__zone-2">
        <span class="container__zone-2--flex">
            <h2>Live Preview</h2>
            <div id="loader"></div>
        </span>

        <iframe id="preview"></iframe>
    </div>

    <template id="container-inputs-template" hidden>
        <div class="container__input-container">
            <input type="text" class="container__input" placeholder="Param">
            {{-- <input type="text" class="container__input" placeholder="Value"> --}}
            <select>
                <option value="string">string</option>
                <option value="int">int</option>
                <option value="float">float</option>
                <option value="bool">bool</option>
                <option value="array">array</option>
                <option value="mixed">mixed</option>
            </select>
            <input type="text" class="container__input" placeholder="Default">
            <input type="checkbox" class="container__input">
            <button class="delete-prop">Delete</button>
        </div>
    </template>

</body>

</html>
