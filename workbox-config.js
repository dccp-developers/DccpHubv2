export default {
	globDirectory: 'public/',
	globPatterns: [
		'**/*.{png,js,css,json,ico,webp,php,txt,webmanifest,xml}'
	],
	swDest: 'public/sw.js',
	ignoreURLParametersMatching: [
		/^utm_/,
		/^fbclid$/
	]
};