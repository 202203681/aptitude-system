const CACHE_NAME = 'sats-v2.0';
const urlsToCache = [
    '/',
    '/index.php',
    '/auth/login.php',
    '/auth/register.php',
    '/user/dashboard.php',
    '/assets/css/style.css',
    '/assets/js/timer.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// Fetch with offline support
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request)
                    .then(response => {
                        if (!response || response.status !== 200) {
                            return response;
                        }
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });
                        return response;
                    });
            })
    );
});

// Sync offline data
self.addEventListener('sync', event => {
    if (event.tag === 'sync-responses') {
        event.waitUntil(syncOfflineResponses());
    }
});

async function syncOfflineResponses() {
    try {
        const responses = await getOfflineResponses();
        for (const response of responses) {
            await fetch('/api/sync_offline.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(response)
            });
        }
        await clearOfflineResponses();
    } catch (error) {
        console.error('Sync failed:', error);
    }
}

// Helper functions for IndexedDB
function getOfflineResponses() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('sats-offline', 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['responses'], 'readonly');
            const store = transaction.objectStore('responses');
            const getAll = store.getAll();
            getAll.onsuccess = () => resolve(getAll.result);
        };
    });
}

function clearOfflineResponses() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('sats-offline', 1);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['responses'], 'readwrite');
            const store = transaction.objectStore('responses');
            store.clear();
            resolve();
        };
    });
}