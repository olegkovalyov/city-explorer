<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// TODO: Import components for Weather, Map, Photos, Favorites

const searchQuery = ref('');
const cityData = ref(null); // To store data fetched from APIs
const isLoading = ref(false);
const error = ref(null);

const searchCity = async () => {
    if (!searchQuery.value) return;
    isLoading.value = true;
    error.value = null;
    cityData.value = null;

    console.log(`Searching for city: ${searchQuery.value}`);

    // TODO: Implement API calls to backend (which then calls external APIs)
    // try {
    //     const response = await axios.get('/api/city-data', { params: { city: searchQuery.value } });
    //     cityData.value = response.data;
    // } catch (err) {
    //     console.error('Error fetching city data:', err);
    //     error.value = 'Failed to fetch city data. Please try again.';
    // } finally {
    //     isLoading.value = false;
    // }

    // Mock delay and data for now
    await new Promise(resolve => setTimeout(resolve, 1000));
    cityData.value = {
        name: searchQuery.value,
        weather: { temp: '25°C', description: 'Sunny' },
        map: { lat: 51.5074, lon: 0.1278 }, // Example coords (London)
        photos: ['photo1.jpg', 'photo2.jpg'] // Example photos
    };
    isLoading.value = false;

};

</script>

<template>
    <Head title="City Explorer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">City Explorer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                        <!-- Search Input -->
                        <div class="mb-6">
                            <label for="city-search" class="block mb-2 text-sm font-medium">Search for a City</label>
                            <div class="flex">
                                <input
                                    type="text"
                                    id="city-search"
                                    v-model="searchQuery"
                                    @keyup.enter="searchCity"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="e.g., London, Tokyo, New York"
                                >
                                <button
                                    @click="searchCity"
                                    :disabled="isLoading || !searchQuery"
                                    class="p-2.5 px-4 text-sm font-medium text-white bg-blue-700 rounded-r-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="!isLoading">Search</span>
                                    <span v-else>Searching...</span>
                                </button>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div v-if="isLoading" class="text-center">
                            Loading city data...
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="text-center text-red-500">
                            {{ error }}
                        </div>

                        <!-- City Data Display Area -->
                        <div v-if="cityData && !isLoading && !error" class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Weather Component (Placeholder) -->
                            <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow">
                                <h3 class="text-lg font-semibold mb-2">Weather in {{ cityData.name }}</h3>
                                <p>Temperature: {{ cityData.weather.temp }}</p>
                                <p>Conditions: {{ cityData.weather.description }}</p>
                                <!-- TODO: Replace with Weather component -->
                            </div>

                            <!-- Map Component (Placeholder) -->
                            <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow">
                                <h3 class="text-lg font-semibold mb-2">Map of {{ cityData.name }}</h3>
                                <p>(Map Component Placeholder: Lat {{ cityData.map.lat }}, Lon {{ cityData.map.lon }})</p>
                                <!-- TODO: Replace with Map component -->
                            </div>

                            <!-- Photos Component (Placeholder) -->
                            <div class="md:col-span-2 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow">
                                <h3 class="text-lg font-semibold mb-2">Photos of {{ cityData.name }}</h3>
                                <p>(Photo Gallery Placeholder: {{ cityData.photos.length }} photos)</p>
                                <!-- TODO: Replace with Photos component -->
                            </div>

                            <!-- Favorite Button (Placeholder) -->
                            <div class="md:col-span-2 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow">
                                <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
                                    Add to Favorites (Placeholder)
                                </button>
                                <!-- TODO: Implement Favorite logic -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any component-specific styles here */
</style>
