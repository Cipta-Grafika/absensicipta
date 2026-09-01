// Progressive Web App (PWA) Service Worker Registration & Installation Helper

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        // Optional: Check for updates periodically
        registration.addEventListener('updatefound', () => {
          const installingWorker = registration.installing;
          if (installingWorker) {
            installingWorker.addEventListener('statechange', () => {
              if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                console.log('[PWA] Konten baru tersedia; silakan muat ulang.');
              }
            });
          }
        });
      })
      .catch((error) => {
        console.warn('[PWA] ServiceWorker registration failed:', error);
      });
  });
}

// Handle PWA Install Prompt
let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
  // Prevent Chrome 67 and earlier from automatically showing the prompt
  e.preventDefault();
  // Stash the event so it can be triggered later
  deferredPrompt = e;
  window.dispatchEvent(new CustomEvent('pwa-installable', { detail: { canInstall: true } }));
});

window.addEventListener('appinstalled', () => {
  deferredPrompt = null;
  window.dispatchEvent(new CustomEvent('pwa-installed'));
  console.log('[PWA] Aplikasi Absensi Cipta berhasil di-install.');
});

window.installPwaApp = async () => {
  if (!deferredPrompt) {
    alert('Aplikasi sudah ter-install atau gunakan opsi "Tambahkan ke Layar Utama" (Add to Home Screen) di menu browser Anda.');
    return;
  }
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') {
    console.log('[PWA] User accepted install prompt');
  }
  deferredPrompt = null;
};
