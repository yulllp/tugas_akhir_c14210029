import "./bootstrap";
import "flowbite";
// import Alpine from "alpinejs";

// window.Alpine = Alpine;

// Alpine.start();

// resources/js/app.js

if ('serviceWorker' in navigator && 'PushManager' in window) {
  window.addEventListener('load', async () => {
    try {
      // 1) Register our service worker file
      const registration = await navigator.serviceWorker.register('/service-worker.js');

      // 2) Ask permission for notifications
      let permission = Notification.permission;
      if (permission === 'default') {
        permission = await Notification.requestPermission();
      }

      if (permission !== 'granted') {
        console.warn('Push notifications permission was not granted');
        return;
      }

      // 3) Subscribe to PushManager with our VAPID public key
      const vapidPublicKey = window.LARAVEL_PUSH_PUBLIC_KEY;
      // Convert the base64 public key to a UInt8 array:
      function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
          .replace(/\-/g, '+')
          .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
          outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
      }
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
      });

      // 4) Send subscription to our backend
      await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(subscription),
      });
    } catch (err) {
      console.error('Error during service worker registration or subscription:', err);
    }
  });
}
