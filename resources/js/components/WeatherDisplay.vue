<script setup>

const props = defineProps({
    weatherData: {
        type: Object,
        default: null,
    },
    weatherLoading: {
        type: Boolean,
        default: false,
    },
    weatherError: {
        type: String,
        default: null,
    },
    timezoneOffset: { // Added this prop
        type: String,
        default: '',
    },
});

// Helper to safely round numbers, returns null if input is not valid
const roundSafely = (value) => {
    const num = Number(value);
    return !isNaN(num) ? Math.round(num) : null;
};

</script>

<template>
    <div class="mt-4 rounded-lg border p-4 dark:border-gray-700">
        <h4 class="text-md mb-2 font-semibold">Current Weather</h4>
        <div v-if="weatherLoading" class="text-sm text-muted-foreground">Loading weather...</div>
        <div v-else-if="weatherError" class="text-sm text-red-600 dark:text-red-400">Error: {{ weatherError }}</div>
        <div v-else-if="weatherData" class="flex items-center space-x-4">
            <img
                v-if="weatherData.icon_url"
                :src="weatherData.icon_url"
                :alt="weatherData.description"
                class="h-12 w-12"
            />
            <div>
                <p class="text-lg font-medium">
                    {{ roundSafely(weatherData.temperature) }}°C
                    <span v-if="weatherData.feels_like !== null" class="text-sm text-muted-foreground"
                    >(feels like {{ roundSafely(weatherData.feels_like) }}°C)</span
                    >
                </p>
                <p class="text-sm text-muted-foreground capitalize">{{ weatherData.description }}</p>
                 <p v-if="weatherData.city_name" class="text-xs text-muted-foreground">({{ weatherData.city_name }})</p>
            </div>
        </div>
         <div v-if="weatherData" class="mt-3 space-y-1 border-t pt-3 text-sm text-muted-foreground dark:border-gray-600">
            <div class="flex justify-between">
                 <span>Humidity:</span>
                 <span v-if="weatherData.humidity !== null">{{ weatherData.humidity }}%</span>
                 <span v-else>N/A</span>
            </div>
             <div class="flex justify-between">
                 <span>Wind:</span>
                 <span v-if="weatherData.wind_speed !== null">{{ weatherData.wind_speed?.toFixed(1) }} m/s</span>
                 <span v-else>N/A</span>
             </div>
        </div>
        <div v-else-if="!weatherLoading && !weatherError" class="text-sm text-muted-foreground">
            Weather data not available for this location.
        </div>
    </div>
</template>
