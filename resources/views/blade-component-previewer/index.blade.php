<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blade Component Previewer</title>

    <style>
        body {
            font-family: sans-serif;
            display: flex;
            height: 100vh;
            margin: 0;
        }

        .container__zone-1 {
            flex: 1;
            padding: 10px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .container__zone-2 {
            flex: 1;
            padding: 10px;
        }

        #preview {
            width: 100%;
            height: 100%;
            border: none;
        }

        .component-btn {
            display: block;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .container__editor-wrap {
            margin-top: 10px;
        }

        textarea {
            width: 100%;
            margin-bottom: 5px;
            height: 80px;
        }

        .container__input-container {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }

        .container__input {
            flex: 1;
            padding: 2px 5px;
        }
    </style>
</head>

<body>
    <div class="container__zone-1">
        <h2>Components</h2>
        <div id="component-btns">
        </div>
        <button id="new-component-btn" class="container__button">➕ New Component</button>

        <h3>Props</h3>
        <div id="inputs-container"></div>
        <button id="add-prop-btn">Add Prop</button>

        <div class="container__editor-wrap">
            <h3>Blade HTML</h3>
            <textarea id="htmlInput"></textarea>

            <h3>CSS</h3>
            <textarea id="cssInput"></textarea>

            <h3>JS</h3>
            <textarea id="jsInput"></textarea>


            <button id="save-component-btn" class="container__button">💾 Save</button>
            <button id="delete-component-btn" class="container__button">🗑️ Delete</button>
        </div>
    </div>

    <div class="container__zone-2">
        <h2>Live Preview</h2>
        <iframe id="preview"></iframe>
    </div>

    <template id="container-inputs-template" hidden>
        <div class="container__input-container">
            <input type="text" class="container__input" placeholder="Param">
            {{-- <input type="text" class="container__input" placeholder="Value"> --}}
            <input type="text" class="container__input" placeholder="Default">
            <button class="delete-prop">Delete</button>
        </div>
    </template>


    <script>
        const htmlInput = document.getElementById('htmlInput');
        const cssInput = document.getElementById('cssInput');
        const jsInput = document.getElementById('jsInput');
        const propsInput = document.getElementById('inputs-container');
        const preview = document.getElementById('preview');
        const componentBtns = document.getElementById('component-btns');
        const addPropBtn = document.getElementById('add-prop-btn');
        const newComponentBtn = document.getElementById('new-component-btn');
        const saveBtn = document.getElementById('save-component-btn');
        const deleteBtn = document.getElementById('delete-component-btn');
        let currentComponent = null;

        const components = @json($components);
    </script>

    <script>
        // ---- Helpers ---- //
        function debounce(fn, delay = 400) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn(...args), delay);
            };
        }

        // Collect props from input fields
        function getProps() {
            const props = [];
            propsInput.querySelectorAll('.container__input-container').forEach(container => {
                const inputs = container.querySelectorAll('input');
                const key = inputs[0].value.trim();
                const def = inputs[1].value.trim();

                if (key) {
                    props.push({
                        key: key,
                        default: def
                    });
                }
            });
            return props;
        }

        // ---- Preview Rendering ---- //
        const renderPreview = debounce(() => {
            if (!currentComponent) return;

            fetch(`/blade-component-previewer/${currentComponent.name}/preview`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        blade: htmlInput.value,
                        css: cssInput.value,
                        js: jsInput.value,
                        props: JSON.stringify(getProps())
                    })
                })
                .then(res => res.json())
                .then(data => {
                    let content = '';
                    if (data.error) {
                        content = data.error;
                    } else {
                        content = data.content;
                    }

                    const doc = preview.contentDocument || preview.contentWindow.document;
                    doc.open();
                    doc.write(content);
                    doc.close();
                })
                .catch(err => console.error('Preview error:', err));
        }, 1000);

        // ---- Component Loader ---- //

        function loadComponent(name) {
            currentComponent = components.find(c => c.name === name);
            if (!currentComponent) return;

            htmlInput.value = currentComponent.blade || '';
            cssInput.value = currentComponent.css || '';
            jsInput.value = currentComponent.js || '';

            // Load props
            propsInput.innerHTML = '';
            if (currentComponent.props) {
                for (const [key, prop] of Object.entries(currentComponent.props)) {
                    addPropRow(key, prop.default);
                }
            }

            renderPreview();
        }

        // ---- Prop Row Generator ---- //
        function addPropRow(key = '', def = '') {
            const node = document.getElementById('container-inputs-template').content.cloneNode(true);
            const inputs = node.querySelectorAll('input');
            const deleteBtn = node.querySelector('button');

            inputs[0].value = key;
            inputs[1].value = def;

            deleteBtn.addEventListener('click', e => {
                e.preventDefault();
                e.target.closest('.container__input-container').remove();
                renderPreview();
            });

            propsInput.appendChild(node);
        }

        // ---- Event Listeners ---- //
        htmlInput.addEventListener('input', renderPreview);
        cssInput.addEventListener('input', renderPreview);
        jsInput.addEventListener('input', renderPreview);
        propsInput.addEventListener('input', renderPreview);

        if (addPropBtn) {
            addPropBtn.addEventListener('click', e => {
                e.preventDefault();
                addPropRow();
            });
        }

        // Generate component buttons dynamically
        if (componentBtns) {
            components.forEach(c => {
                const btn = document.createElement('button');
                btn.textContent = c.name;
                btn.className = 'component-btn';
                btn.dataset.name = c.name;
                btn.addEventListener('click', () => loadComponent(c.name));
                componentBtns.appendChild(btn);
            });
        }

        // ---- Init ---- //
        if (components.length) {
            loadComponent(components[0].name);
        }
    </script>

    <script>
        function addComponentButton(component) {
            const btn = document.createElement('button');
            btn.textContent = component.name;
            btn.className = 'component-btn';
            btn.dataset.name = component.name;
            btn.addEventListener('click', () => loadComponent(component.name));
            componentBtns.appendChild(btn);
        }

        // Handle new component creation
        newComponentBtn.addEventListener('click', () => {
            const name = prompt("Enter new component name (e.g. 'ButtonComponent')");
            if (!name) return;

            fetch("{{ route('blade-component-previewer.create') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        components.push(data.component);
                        addComponentButton(data.component);
                        loadComponent(data.component.name);
                    } else {
                        alert("Error creating component");
                    }
                })
                .catch(err => console.error(err));
        });
    </script>

    <script>
        saveBtn.addEventListener('click', () => {
            if (!currentComponent) {
                alert("No component selected");
                return;
            }

            const html = htmlInput.value;
            const css = cssInput.value;
            const js = jsInput.value;
            const props = getProps();

            fetch("{{ route('blade-component-previewer.update') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: currentComponent.name,
                        html,
                        css,
                        js,
                        props
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        currentComponent.blade = html;
                        currentComponent.css = css;
                        currentComponent.js = js;
                        currentComponent.props = props;
                        renderPreview();
                    }
                })
                .catch(err => console.error(err));
        });
    </script>


    <script>
        deleteBtn.addEventListener('click', () => {
            if (!currentComponent) {
                alert("No component selected");
                return;
            }

            fetch("{{ route('blade-component-previewer.delete') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: currentComponent.name,
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    }
                })
                .catch(err => console.error(err));
        });
    </script>

</body>

</html>
