module.exports = {
	globDirectory: 'public/',
	globPatterns: [
		'**/*.{png,js,css,json,ico,webp,txt,webmanifest,xml}',
		'build/assets/*.{js,css}',
		'offline.html',
		'site.webmanifest',
		'manifest.json',
		'favicon*.png',
		'android-chrome-*.png',
		'apple-touch-icon.png'
	],
	swDest: 'public/sw.js',
	ignoreURLParametersMatching: [
		/^utm_/,
		/^fbclid$/
	],
	skipWaiting: true,
	clientsClaim: true,
	runtimeCaching: [
		{
			urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
			handler: 'CacheFirst',
			options: {
				cacheName: 'images',
				expiration: {
					maxEntries: 60,
					maxAgeSeconds: 30 * 24 * 60 * 60 // 30 days
				}
			}
		},
		{
			urlPattern: /\.(?:js|css)$/,
			handler: 'StaleWhileRevalidate',
			options: {
				cacheName: 'static-resources'
			}
		},
		{
			urlPattern: new RegExp('^https://fonts.(?:googleapis|gstatic).com/(.*)'),
			handler: 'CacheFirst',
			options: {
				cacheName: 'google-fonts',
				expiration: {
					maxEntries: 30,
					maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
				}
			}
		},
		{
			urlPattern: /\/api\//,
			handler: 'NetworkFirst',
			options: {
				cacheName: 'api-cache',
				networkTimeoutSeconds: 10,
				expiration: {
					maxEntries: 50,
					maxAgeSeconds: 5 * 60 // 5 minutes
				}
			}
		},
		{
			urlPattern: /.*/, // Catch-all for everything else
			handler: 'NetworkFirst',
			options: {
				cacheName: 'dynamic-content',
				networkTimeoutSeconds: 10,
				expiration: {
					maxEntries: 100,
					maxAgeSeconds: 24 * 60 * 60 // 24 hours
				}
			}
		}
	],
	navigateFallback: '/offline.html',
	navigateFallbackDenylist: [/^\/admin/, /^\/api/, /^\/storage/]
};
