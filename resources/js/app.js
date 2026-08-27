import { createApp } from 'vue';

document.querySelectorAll('[data-vue-component]').forEach((el) => {
    const componentName = el.dataset.vueComponent;
    import(`./components/${componentName}.vue`).then((module) => {
        const props = {};

        for (const [key, value] of Object.entries(el.dataset)) {
            if (key === 'vueComponent') continue;

            try {
                props[key] = JSON.parse(value);
            } catch {
                props[key] = value;
            }
        }

        createApp(module.default, props).mount(el);
    });
});
