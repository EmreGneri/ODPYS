import { createApp } from 'vue';

document.querySelectorAll('[data-vue-component]').forEach((el) => {
    const componentName = el.dataset.vueComponent;
    import(`./components/${componentName}.vue`).then((module) => {
        const app = createApp(module.default, {
            action: el.dataset.action,
            method: el.dataset.method || 'POST',
            initialGradeNumber: el.dataset.initialGradeNumber || '',
            csrfToken: el.dataset.csrfToken,
        });
        app.mount(el);
    });
});
