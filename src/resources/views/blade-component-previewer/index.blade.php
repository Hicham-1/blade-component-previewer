<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blade Component Previewer</title>

    <style>
        {!! file_get_contents($stylePath) !!}
    </style>
    <script>
        const components = @json($components);

        {!! file_get_contents($jsPath) !!}
    </script>
</head>

<body>
    <div class="container">
        <section class="container__zone-1">
            <div id="component-btns">
            </div>
            <button class="container__button">add</button>
            <div id="inputs-container">

            </div>
            <div class="container__editor-wrap">
                <textarea id="htmlInput" placeholder="HTML">
                    <h1>Hello World</h1>
                    <p>This comes from the HTML textarea.</p>
                </textarea>

                <textarea id="cssInput" placeholder="CSS">
                    body { font-family: sans-serif; color: darkblue; }
                    h1 { color: crimson; }
                </textarea>

                <textarea id="jsInput" placeholder="JS">
                    document.querySelector("h1").addEventListener("click", () => {
                    alert("You clicked the title!");
                    });
                </textarea>
            </div>
        </section>
        <section class="container__zone-2">
            <iframe id="preview"></iframe>
        </section>
    </div>
</body>

<template id="container-inputs-template" hidden>
    <div class="container__input-container">
        <input type="text" class="container__input" placeholder="param">
        <input type="text" class="container__input" placeholder="value">
        <input type="text" class="container__input" placeholder="default value">
        <button class="container__button">delete</button>
    </div>
</template>

</html>
