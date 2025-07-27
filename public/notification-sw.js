// Service Worker for Web Push Notifications

const CACHE_NAME = 'dccp-hub-notifications-v1';

// Install event
self.addEventListener('install', (event) => {
  console.log('Notification Service Worker installing...');
  self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
  console.log('Notification Service Worker activating...');
  event.waitUntil(self.clients.claim());
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
  console.log('Push event received:', event);

  let notificationData = {};
  
  if (event.data) {
    try {
      notificationData = event.data.json();
    } catch (e) {
      notificationData = {
        title: 'New Notification',
        body: event.data.text() || 'You have a new notification',
        icon: '/images/notification-icon.png',
        badge: '/images/notification-badge.png'
      };
    }
  }

  const options = {
    body: notificationData.body || notificationData.message || 'You have a new notification',
    icon: notificationData.icon || '/images/notification-icon.png',
    badge: notificationData.badge || '/images/notification-badge.png',
    image: notificationData.image,
    data: notificationData.data || {},
    actions: notificationData.actions || [],
    requireInteraction: notificationData.priority === 'urgent',
    silent: notificationData.priority === 'low',
    tag: notificationData.tag || 'faculty-notification',
    renotify: true,
    timestamp: Date.now(),
    vibrate: notificationData.priority === 'urgent' ? [200, 100, 200] : [100]
  };

  // Add action buttons based on notification data
  if (notificationData.action_url && notificationData.action_text) {
    options.actions = [
      {
        action: 'open',
        title: notificationData.action_text,
        icon: '/images/action-icon.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/images/dismiss-icon.png'
      }
    ];
  } else {
    options.actions = [
      {
        action: 'open',
        title: 'Open App',
        icon: '/images/action-icon.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/images/dismiss-icon.png'
      }
    ];
  }

  const title = notificationData.title || 'DCCP Hub Notification';

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('Notification clicked:', event);

  event.notification.close();

  const action = event.action;
  const notificationData = event.notification.data;

  if (action === 'dismiss') {
    // Just close the notification
    return;
  }

  // Determine the URL to open
  let urlToOpen = '/faculty/dashboard'; // Default URL

  if (action === 'open' && notificationData.action_url) {
    urlToOpen = notificationData.action_url;
  } else if (notificationData.action_url) {
    urlToOpen = notificationData.action_url;
  }

  // Open the URL
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // Check if there's already a window/tab open with the target URL
      for (const client of clientList) {
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus();
        }
      }

      // If no existing window/tab, open a new one
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});

// Notification close event
self.addEventListener('notificationclose', (event) => {
  console.log('Notification closed:', event);
  
  // You can track notification dismissals here
  const notificationData = event.notification.data;
  
  // Send analytics or tracking data if needed
  if (notificationData.tracking_id) {
    // Track notification dismissal
    fetch('/api/notifications/track-dismissal', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        tracking_id: notificationData.tracking_id,
        action: 'dismissed'
      })
    }).catch(err => console.log('Failed to track dismissal:', err));
  }
});

// Message event for communication with the main thread
self.addEventListener('message', (event) => {
  console.log('Notification Service Worker received message:', event.data);

  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
});

console.log('Notification Service Worker loaded successfully');
