<script>
    (() => {
        const themeRoot = document.documentElement;
        const normalizeMode = (value) => {
            const mode = typeof value === 'string' ? value.trim().toLowerCase() : 'auto';

            return ['light', 'dark', 'auto'].includes(mode) ? mode : 'auto';
        };

        const normalizeTimeValue = (value, fallback) => {
            if (typeof value !== 'string') {
                return fallback;
            }

            const trimmed = value.trim();

            return /^([01]\d|2[0-3]):[0-5]\d$/.test(trimmed) ? trimmed : fallback;
        };

        const toMinutes = (timeValue) => {
            const [hours, minutes] = timeValue.split(':').map(Number);

            return (hours * 60) + minutes;
        };

        const resolveTheme = (mode, dayStart, nightStart, now) => {
            if (mode === 'light' || mode === 'dark') {
                return mode;
            }

            const dayMinutes = toMinutes(dayStart);
            const nightMinutes = toMinutes(nightStart);
            const currentMinutes = now.getHours() * 60 + now.getMinutes();

            if (dayMinutes === nightMinutes) {
                return 'light';
            }

            if (dayMinutes < nightMinutes) {
                return currentMinutes >= dayMinutes && currentMinutes < nightMinutes ? 'light' : 'dark';
            }

            return currentMinutes >= dayMinutes || currentMinutes < nightMinutes ? 'light' : 'dark';
        };

        const mode = normalizeMode(@json($themeMode ?? 'auto'));
        const dayStart = normalizeTimeValue(@json($themeDayStart ?? '07:00'), '07:00');
        const nightStart = normalizeTimeValue(@json($themeNightStart ?? '19:00'), '19:00');
        const theme = resolveTheme(mode, dayStart, nightStart, new Date());

        themeRoot.setAttribute('data-theme-mode', mode);
        themeRoot.setAttribute('data-theme-day-start', dayStart);
        themeRoot.setAttribute('data-theme-night-start', nightStart);
        themeRoot.setAttribute('data-theme', theme);
        themeRoot.style.colorScheme = theme;
    })();
</script>
