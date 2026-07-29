<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ isset($title) && $title !== '' && $title !== 'Laravel' ? $title . ' - APELS' : 'APELS - Adaptive Professional English Learning System' }}</title>

<link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('images/icon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/icon.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
