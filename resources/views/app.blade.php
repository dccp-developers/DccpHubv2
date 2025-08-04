<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DCCPHub') }}</title>

    <link rel="canonical" href="{{ url()->current() }}">

    <!-- PWA Meta Tags -->
    <meta name="description" content="DCCPHub - Your academic hub for DCCP">

    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    @laravelPWA


    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "DCCPhub",
            "url": "https://portal.dccp.edu.ph/",
            "image": "https://portal.dccp.edu.ph/images/og.webp",
            "description": "A modern Laravel School Portal for DCCP",
            "applicationCategory": "DeveloperTool",
            "operatingSystem": "All",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "PHP",
                "category": "Free"
            }
        }
    </script>

    <!-- Umami Analytics -->
    <script defer src="http://dccp-umami-63a1ab-46-250-224-248.traefik.me/script.js" data-website-id="765decc6-fc2b-476e-bb82-63a7ceab5950"></script>

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
     @voletStyles
</head>

<body class="font-sans antialiased">
    @inertia
      @volet
</body>

</html>
