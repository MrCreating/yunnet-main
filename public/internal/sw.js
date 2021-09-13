const cacheName = "unt_data";

let codeGiven = false;
const offlineCode = `console.log('[!] OFFLINE MODE!');`;

this.addEventListener("install", function (event) {
	event.waitUntil(
		caches.open(cacheName).then(function(cache) {
	    	return cache.addAll(
	        	[
	        		'/favicon.ico',
	        		'/manifest.json',
	        		'/sitemap.xml',
	        		'/favicon/android-chrome-192x192.png',
	        		'/favicon/android-chrome-512x512.png',
	        		'/favicon/apple-touch-icon.png',
	        		'/favicon/favicon-16x16.png',
	        		'/favicon/favicon-32x32.png',
	        		'/internal/offline.js',
	        	]
	      	);
   		})
	);
})

this.addEventListener("fetch", function (event) {
	if (event.request.destination === "image" && "caches" in this) {
		return event.respondWith(
			caches.open(cacheName).then(function (cache) {
				return cache.match(event.request).then(function (response) {
					return response 
						||
							fetch(event.request).then(function (response) {
								cache.put(event.request, response.clone());

								return response;
							})
				})
			})
		);
	}

	if (!navigator.onLine && "caches" in this) {
		return event.respondWith(
			caches.open(cacheName).then(function (cache) {
				return cache.match(event.request).then(function (response) {
					if (response) return response 

					let contentType = 'text/html';
					let contentText = '<script src="https://yunnet.ru/internal/offline.js"></script>';

					if (event.request.destination === 'style') {
						contentText = '';
						contentType = 'text/css';
					}
					if (event.request.destination === 'script') {
						contentText = !codeGiven ? offlineCode : '{}';
						contentType = 'text/javascript';

						codeGiven = true;
					}

					let result = new Response(contentText, {
						headers: {
							'Content-Type': contentType
						}
					});

					return result;
				})
			})
  		);
	}
})