import './bootstrap';
import { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: (name) =>
        // laravel-vite-plugin's resolvePageComponent resolves to the raw
        // module namespace ({ default: Component }); Inertia unwraps
        // `.default` internally at runtime. The cast bridges a type-only gap
        // between the two packages' declared types for this exact shape.
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob<{ default: ComponentType }>('./Pages/**/*.tsx'),
        ) as unknown as Promise<ComponentType>,
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
