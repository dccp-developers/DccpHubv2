// Debug script to check Pusher environment variables
console.log('=== Pusher Environment Variables Debug ===');
console.log('VITE_PUSHER_APP_ID:', import.meta.env.VITE_PUSHER_APP_ID);
console.log('VITE_PUSHER_APP_KEY:', import.meta.env.VITE_PUSHER_APP_KEY);
console.log('VITE_PUSHER_APP_CLUSTER:', import.meta.env.VITE_PUSHER_APP_CLUSTER);
console.log('All env vars:', import.meta.env);

// Test Pusher connection
import Pusher from 'pusher-js';

if (import.meta.env.VITE_PUSHER_APP_KEY && import.meta.env.VITE_PUSHER_APP_CLUSTER) {
  console.log('Attempting to connect to Pusher...');
  
  const pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    encrypted: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      }
    }
  });

  pusher.connection.bind('connected', () => {
    console.log('✅ Pusher connected successfully!');
  });

  pusher.connection.bind('error', (err) => {
    console.error('❌ Pusher connection error:', err);
  });

  pusher.connection.bind('disconnected', () => {
    console.log('🔌 Pusher disconnected');
  });
} else {
  console.error('❌ Missing Pusher environment variables');
}
