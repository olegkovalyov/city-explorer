import { getCsrfToken } from '@/utils/auth';
import { computed, ref } from 'vue';

// Map country codes to currencies (RESTORED ORIGINAL VERSION from Dashboard)
const countryCurrencyMap = {
    US: 'USD ($)',
    CA: 'CAD ($)',
    MX: 'MXN ($)',
    GB: 'GBP (£)',
    DE: 'EUR (€)',
    FR: 'EUR (€)',
    ES: 'EUR (€)',
    IT: 'EUR (€)',
    NL: 'EUR (€)',
    PT: 'EUR (€)', // Eurozone
    AU: 'AUD ($)',
    NZ: 'NZD ($)',
    JP: 'JPY (¥)',
    CN: 'CNY (¥)',
    KR: 'KRW (₩)',
    IN: 'INR (₹)',
    BR: 'BRL (R$)',
    RU: 'RUB (₽)', // Russia
    UA: 'UAH (₴)', // Ukraine (updated symbol)
    CH: 'CHF (Fr)',
    SE: 'SEK (kr)',
    NO: 'NOK (kr)',
    DK: 'DKK (kr)',
    // Add more common ones as needed
};

export function useWeather() {
    const weatherData = ref(null);
    const weatherLoading = ref(false);
    const weatherError = ref(null);

    async function getWeather(lat, lng) {
        if (!lat || !lng) {
            weatherError.value = 'Invalid coordinates provided.';
            return;
        }
        weatherLoading.value = true;
        weatherError.value = null;
        weatherData.value = null; // Reset previous data
        const csrfToken = getCsrfToken();

        try {
            const response = await fetch(`/api/weather?latitude=${lat}&longitude=${lng}`, {
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                let errorMessage = `Failed to fetch weather. Status: ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMessage += `: ${errorData.message || 'Unknown error'}`;
                    // eslint-disable-next-line @typescript-eslint/no-unused-vars
                } catch (e) {
                    // Ignore if response is not JSON
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();
            weatherData.value = data;
        } catch (err) {
            weatherError.value = err.message || 'Failed to load weather.';
            console.error('Fetching weather error:', err);
            // Consider removing toast from here, let the component handle UI feedback
        } finally {
            weatherLoading.value = false;
        }
    }

    // --- Computed Properties (depend on weatherData) ---
    // RESTORED ORIGINAL LOGIC from Dashboard
    const localTime = computed(() => {
        if (!weatherData.value || weatherData.value.timezone_offset_seconds === null || weatherData.value.timezone_offset_seconds === undefined) {
            return 'N/A';
        }
        try {
            // Use timezone_offset_seconds
            const offsetSeconds = weatherData.value.timezone_offset_seconds;
            const localDate = new Date(Date.now() + offsetSeconds * 1000);

            // Format time as HH:MM in UTC (since we manually added offset)
            const hours = localDate.getUTCHours().toString().padStart(2, '0');
            const minutes = localDate.getUTCMinutes().toString().padStart(2, '0');

            // Format offset string (UTC+/-HH:MM)
            const offsetHours = Math.floor(Math.abs(offsetSeconds) / 3600);
            const offsetMinutes = Math.floor((Math.abs(offsetSeconds) % 3600) / 60);
            const offsetSign = offsetSeconds >= 0 ? '+' : '-';
            const offsetString = `UTC${offsetSign}${offsetHours.toString().padStart(2, '0')}:${offsetMinutes.toString().padStart(2, '0')}`;

            return `${hours}:${minutes} (${offsetString})`;
        } catch (e) {
            console.error('Error calculating local time:', e);
            return 'Error';
        }
    });

    // RESTORED ORIGINAL LOGIC from Dashboard
    const localCurrency = computed(() => {
        if (!weatherData.value || !weatherData.value.country_code) {
            return 'N/A';
        }
        return countryCurrencyMap[weatherData.value.country_code] || `Unknown (${weatherData.value.country_code})`;
    });

    return {
        weatherData,
        weatherLoading,
        weatherError,
        getWeather,
        localTime,
        localCurrency,
    };
}
