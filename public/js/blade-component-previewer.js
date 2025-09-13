
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
const loaderEl = document.getElementById('loader');

const editorBlade = ace.edit("php-editor");
const editorCss = ace.edit("css-editor");
const editorJs = ace.edit("js-editor");

let currentComponent = null;


// ---- Helpers ---- //
function debounce(fn, delay = 400) {
    let timeout;
    return (...args) => {
        loaderEl.style.visibility = 'visible';
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            loaderEl.style.visibility = 'hidden';
            fn(...args);
        }, delay);
    };
}

// Collect props from input fields
function getProps() {
    const props = [];
    propsInput.querySelectorAll('.container__input-container').forEach(container => {
        const inputs = container.querySelectorAll('input');
        const select = container.querySelector('select');

        const key = inputs[0].value.trim();
        const type = select.value || 'string';
        const def = inputs[1].value.trim();
        const nullable = inputs[2].checked;

        if (key) {
            props.push({
                key: key,
                default: def,
                type: type,
                nullable: nullable,
            });
        }
    });
    return props;
}

// ---- Preview Rendering ---- //
const renderPreview = debounce(() => {
    if (!currentComponent) return;

    let urlPreview = previewRoute.replace('__NAME__', currentComponent.name);

    fetch(urlPreview, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
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
}, 500);

// ---- Component Loader ---- //

function loadComponent(name) {
    currentComponent = components.find(c => c.name === name);
    if (!currentComponent) return;

    const currentComponentName = currentComponent.name;
    const allComponents = document.querySelectorAll('[data-name]');
    allComponents.forEach(el => el.classList.remove('current-component'));
    const currentElement = document.querySelector(`[data-name="${currentComponentName}"]`);
    if (currentElement) {
        currentElement.classList.add('current-component');
    }


    htmlInput.value = currentComponent.blade || '';
    editorBlade.setValue(currentComponent.blade || '');

    cssInput.value = currentComponent.css || '';
    editorCss.setValue(currentComponent.css || '');

    jsInput.value = currentComponent.js || '';
    editorJs.setValue(currentComponent.js || '');

    // Load props
    propsInput.innerHTML = '';
    if (currentComponent.props) {
        for (const [key, prop] of Object.entries(currentComponent.props)) {
            addPropRow(key, prop.default, prop.nullable, prop.type);
        }
    }

    renderPreview();
}

// ---- Prop Row Generator ---- //
function addPropRow(key = '', def = '', nullable = false, type = 'string') {
    const node = document.getElementById('container-inputs-template').content.cloneNode(true);
    const inputs = node.querySelectorAll('input');
    const select = node.querySelector('select');
    const deleteBtn = node.querySelector('button');

    inputs[0].value = key;
    select.value = type;
    inputs[1].value = def;
    inputs[2].checked = !!nullable;

    deleteBtn.addEventListener('click', e => {
        if (!confirm('Are you sure you want to delete this Prop?')) {
            return;
        }

        e.preventDefault();
        e.target.closest('.container__input-container').remove();
        renderPreview();
    });

    propsInput.appendChild(node);
}

// ---- Event Listeners ---- //
// htmlInput.addEventListener('input', renderPreview);
// cssInput.addEventListener('input', renderPreview);
// jsInput.addEventListener('input', renderPreview);
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

    fetch(createRoute, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
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



saveBtn.addEventListener('click', () => {
    if (!currentComponent) {
        alert("No component selected");
        return;
    }

    const html = htmlInput.value;
    const css = cssInput.value;
    const js = jsInput.value;
    const props = getProps();

    fetch(updateRoute, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
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



deleteBtn.addEventListener('click', () => {
    if (!currentComponent) {
        alert("No component selected");
        return;
    }

    if (!confirm('Are you sure you want to delete this Component?')) {
        return;
    }

    fetch(deleteRoute, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
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



document.addEventListener("DOMContentLoaded", () => {
    editorBlade.setTheme("ace/theme/dracula"); // or "github", "dracula", etc.
    editorBlade.session.setMode("ace/mode/html");

    editorCss.setTheme("ace/theme/dracula");
    editorCss.session.setMode("ace/mode/css");

    editorJs.setTheme("ace/theme/dracula");
    editorJs.session.setMode("ace/mode/javascript");

    // Enable autocomplete
    editorBlade.setOptions({
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true,
        enableSnippets: true,
        fontSize: "14px"
    });
    editorCss.setOptions({
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true,
        enableSnippets: true,
        fontSize: "14px"
    });
    editorJs.setOptions({
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true,
        enableSnippets: true,
        fontSize: "14px"
    });

    // Blade directives autocompletion
    const bladeCompleter = {
        getCompletions: function (editor, session, pos, prefix, callback) {
            if (editor !== editorBlade) {
                return callback(null, []); // no suggestions
            }

            callback(null, keywords.map(function (word) {
                return {
                    caption: word,
                    value: word,
                    meta: "blade"
                };
            }));
        }
    };
    // Add Blade completer
    ace.require("ace/ext/language_tools").addCompleter(bladeCompleter);

    editorBlade.session.on('change', function () {
        htmlInput.value = editorBlade.getValue();
        renderPreview();
    });

    editorCss.session.on('change', function () {
        cssInput.value = editorCss.getValue();
        renderPreview();
    });

    editorJs.session.on('change', function () {
        jsInput.value = editorJs.getValue();
        renderPreview();
    });
});
