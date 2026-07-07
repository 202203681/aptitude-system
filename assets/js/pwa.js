// Register Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(registration => {
            console.log('Service Worker registered:', registration);
        })
        .catch(error => {
            console.error('Service Worker registration failed:', error);
        });
}

// Background sync for offline responses
if ('sync' in navigator.serviceWorker) {
    navigator.serviceWorker.ready.then(registration => {
        registration.sync.register('sync-responses');
    });
}

// Check online status
window.addEventListener('online', () => {
    console.log('Back online - syncing data...');
    syncOfflineData();
});

window.addEventListener('offline', () => {
    console.log('Offline mode - saving locally');
});

async function syncOfflineData() {
    const responses = await getOfflineResponses();
    if (responses.length > 0) {
        fetch('/api/sync_offline.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ responses })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                clearSyncedResponses(responses.map(r => r.id));
            }
        });
    }
}