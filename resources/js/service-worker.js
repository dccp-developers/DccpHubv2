// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/',
      });
      console.log('Service Worker registered with scope:', registration.scope);

      // Check for updates to the Service Worker
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        console.log('Service Worker update found!');

        newWorker.addEventListener('statechange', () => {
          console.log('Service Worker state changed:', newWorker.state);
        });
      });

      // Handle Service Worker updates
      let refreshing = false;
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!refreshing) {
          refreshing = true;
          console.log('Controller changed, refreshing page...');
          window.location.reload();
        }
      });
    } catch (error) {
      console.error('Service Worker registration failed:', error);
    }
  });
}
