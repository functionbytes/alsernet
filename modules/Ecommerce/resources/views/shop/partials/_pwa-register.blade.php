<script>
// Service Worker registration + PWA install prompt
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/sw-shop.js', { scope: '/' })
            .catch(function () { /* silent fail */ });
    });
}

// Install prompt
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredInstallPrompt = e;
    if (! localStorage.getItem('pwa_install_dismissed') && ! window.matchMedia('(display-mode: standalone)').matches) {
        showInstallBanner();
    }
});

function showInstallBanner() {
    if (document.getElementById('pwa-install-banner')) return;
    const banner = document.createElement('div');
    banner.id = 'pwa-install-banner';
    banner.innerHTML = `
        <div style="position:fixed;bottom:90px;left:16px;right:16px;background:#1a2030;color:#fff;padding:14px 18px;border-radius:8px;z-index:9990;box-shadow:0 4px 16px rgba(0,0,0,0.2);max-width:420px;margin-left:auto;margin-right:auto;display:flex;align-items:center;gap:12px">
            <i class="fas fa-mobile-screen fa-2x"></i>
            <div style="flex:1">
                <strong style="display:block;font-size:14px;margin-bottom:2px">Instala nuestra app</strong>
                <small style="opacity:0.8">Acceso rápido y offline</small>
            </div>
            <button id="pwa-install-btn" style="background:#b10100;color:#fff;border:none;padding:8px 14px;border-radius:6px;font-weight:600;cursor:pointer">Instalar</button>
            <button id="pwa-dismiss-btn" style="background:transparent;border:none;color:rgba(255,255,255,0.6);font-size:20px;cursor:pointer;padding:4px 8px">&times;</button>
        </div>
    `;
    document.body.appendChild(banner);

    document.getElementById('pwa-install-btn').addEventListener('click', async function () {
        if (! deferredInstallPrompt) return;
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        banner.remove();
    });

    document.getElementById('pwa-dismiss-btn').addEventListener('click', function () {
        localStorage.setItem('pwa_install_dismissed', '1');
        banner.remove();
    });
}

// Notification subscription helper (push notifications)
window.requestPushNotifications = async function () {
    if (! ('Notification' in window) || ! ('serviceWorker' in navigator)) {
        if (typeof toastr !== 'undefined') toastr.warning('Tu navegador no soporta notificaciones push');
        return false;
    }
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return false;
    if (typeof toastr !== 'undefined') toastr.success('Notificaciones activadas');
    return true;
};
</script>
