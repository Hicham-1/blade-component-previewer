document.addEventListener('DOMContentLoaded', function () {
    const htmlInput = document.getElementById('htmlInput');
    const cssInput = document.getElementById('cssInput');
    const jsInput = document.getElementById('jsInput');
    const preview = document.getElementById('preview');

    function render() {
        const html = htmlInput.value;
        const css = `<style>${cssInput.value}</style>`;
        const js = `<script>${jsInput.value}<\/script>`;

        const doc = `
                <!doctype html>
                <html>
                <head>${css}</head>
                <body>${html}${js}</body>
                </html>
            `;
        preview.srcdoc = doc;
    }

    htmlInput.addEventListener('input', render);
    cssInput.addEventListener('input', render);
    jsInput.addEventListener('input', render);

    Object.keys(components).forEach(component => {
        const componentBtn = document.createElement('button');
        componentBtn.innerText = component;
        componentBtn.title = components[component]['full-class'];
        componentBtn.addEventListener('click', () => displayComponent(components[component]));

        const container = document.getElementById('component-btns');

        container.appendChild(componentBtn);
    });

    function displayComponent(component) {
        console.log(component);

    }

    // Initial render
    render();
});