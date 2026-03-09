    </main>
</div>
<script>
// Basic PWA install prompt for the driver dashboard
let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const banner = document.getElementById('pwa-install-banner');
    if (banner) banner.classList.remove('hidden');
});

document.addEventListener('DOMContentLoaded', () => {
    const installBtn = document.getElementById('pwa-install-button');
    const closeBtn   = document.getElementById('pwa-install-close');
    const banner     = document.getElementById('pwa-install-banner');

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted' && banner) {
                banner.classList.add('hidden');
            }
            deferredPrompt = null;
        });
    }

    if (closeBtn && banner) {
        closeBtn.addEventListener('click', () => banner.classList.add('hidden'));
    }
});
</script>

<!-- Compact mobile-friendly PWA banner -->
<div id="pwa-install-banner"
     class="hidden fixed bottom-3 left-3 right-3 sm:right-auto sm:max-w-xs z-30 bg-white border border-slate-200 rounded-xl shadow-lg px-3 py-2 flex items-center justify-between gap-2 text-[11px]">
    <div>
        <div class="font-semibold text-slate-800">Install driver app</div>
        <div class="text-[10px] text-slate-500">Add this dashboard to your home screen for quick access.</div>
    </div>
    <div class="flex items-center gap-1">
        <button id="pwa-install-button"
                class="px-2 py-1 rounded-lg bg-emerald-600 text-white text-[10px] font-medium">
            Install
        </button>
        <button id="pwa-install-close"
                class="px-1 py-1 rounded-lg text-slate-400 hover:bg-slate-100">
            ✕
        </button>
    </div>
</div>

</body>
</html>
