import { createInertiaApp } from '@inertiajs/vue3'
import { createHead } from '@unhead/vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { CapoPlugin } from 'unhead'
import { createApp, h } from 'vue'
import { ZiggyVue } from 'ziggy-js'
import './bootstrap'
import '../css/app.css'

// Import Capacitor Social Login handler
import { initializeSocialLogin, setupDeepLinkListener, handleDeepLinkCallback } from './utils/capacitor-social-login.js'

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/',
      });
      console.log('Service Worker registered with scope:', registration.scope);
    } catch (error) {
      console.error('Service Worker registration failed:', error);
    }
  });
}

// Initialize Social Login for Capacitor
document.addEventListener('DOMContentLoaded', async () => {
  await initializeSocialLogin();
  setupDeepLinkListener();
});

/**
 * This is used from unhead plugin to use seo meta tags
 * @see {@link https://unhead.unjs.io/setup/unhead/introduction} For createHead instance
 * @see {@link https://unhead.unjs.io/plugins/plugins/capo} For CapoPlugin
 */
const head = createHead({
  plugins: [
    CapoPlugin(),
  ],
})

createInertiaApp({
  resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(head)
      .mount(el)
  },
  progress: {
    color: '#4B5563',
  },
})
