<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

{{-- Appearance (Settings > Appearance): light, dark or the system setting, remembered in this browser. Runs before
     the CSS so the page never flashes in the wrong theme. OmniaUI follows data-theme on <html>. wire:navigate gives
     <html> the attributes of the new page, so the theme is applied again on every swap. --}}
<script data-navigate-once>
    (() => {
        const apply = () => {
            const theme = localStorage.getItem('appearance');
            if (theme === 'light' || theme === 'dark') document.documentElement.dataset.theme = theme;
            else delete document.documentElement.dataset.theme;
        };
        apply();
        document.addEventListener('livewire:navigating', (event) => event.detail.onSwap(apply));
        document.addEventListener('livewire:navigated', apply);
    })();
</script>

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
