// Vyomastra offline support, version 1.12.1
const CACHE = 'vyomastra-play-v1';
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (event) {
	event.waitUntil(caches.keys().then(function (names) {
		return Promise.all(names.filter(function (n) { return n.indexOf('vyomastra-play-') === 0 && n !== CACHE; }).map(function (n) { return caches.delete(n); }));
	}).then(function () { return self.clients.claim(); }));
});
self.addEventListener('fetch', function (event) {
	var request = event.request, url = new URL(request.url);
	if (request.method !== 'GET' || url.origin !== location.origin || url.pathname.indexOf(self.registration.scope.replace(location.origin, '')) !== 0) { return; }
	event.respondWith(fetch(request).then(function (response) {
		if (response.ok && response.status === 200) {
			var copy = response.clone();
			caches.open(CACHE).then(function (cache) { return cache.put(request, copy); }).catch(function () {});
		}
		return response;
	}).catch(function () {
		return caches.match(request, { ignoreSearch: true }).then(function (saved) { return saved || Response.error(); });
	}));
});
