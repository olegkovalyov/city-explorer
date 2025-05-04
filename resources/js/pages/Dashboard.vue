<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';
import 'mapbox-gl/dist/mapbox-gl.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import WeatherDisplay from '@/Components/WeatherDisplay.vue';
import LocalInfoCard from '@/Components/LocalInfoCard.vue';
import SearchResults from '@/Components/SearchResults.vue';
import FavoritesList from '@/Components/FavoritesList.vue';
import GalleryModal from '@/Components/GalleryModal.vue';

import { useFavorites } from '@/composables/useFavorites.js';
import { useGalleryModal } from '@/composables/useGalleryModal.js';
import { useMapbox } from '@/composables/useMapbox.js';
import { usePlaces } from '@/composables/usePlaces.js';
import { useWeather } from '@/composables/useWeather.js';

const props = defineProps({
    mapboxToken: String,
});

const mapContainer = ref(null);
const geocoderContainer = ref(null);

// Ref for controlling the active tab (Search Results / Favorites)
const activeTab = ref('search');

const { coordinates } = useMapbox(props.mapboxToken, mapContainer, geocoderContainer);

const { favoritePlaces, favoritesLoading, favoritesError, isFavorite, fetchFavorites, handleToggleFavorite } = useFavorites();

const { weatherData, weatherLoading, weatherError, getWeather, localTime, localCurrency } = useWeather();

const { places, placesLoading, placesError, getPlaces } = usePlaces();

const { galleryPlace, isGalleryOpen, galleryLoading, galleryError, openGallery } = useGalleryModal();

onMounted(() => {
    fetchFavorites();
});

watch(
    coordinates,
    (newCoords) => {
        if (newCoords) {
            getWeather(newCoords.latitude, newCoords.longitude);
            getPlaces(newCoords.latitude, newCoords.longitude);
        }
    },
    { immediate: true },
);

onUnmounted(() => {});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                            <div class="lg:col-span-2">
                                <h3 class="mb-4 text-lg font-medium">Find a Location</h3>
                                <!-- Container for the external Mapbox Geocoder -->
                                <div ref="geocoderContainer" class="mb-4"></div>
                                <p class="text-xs text-muted-foreground">Enter an address or place name above.</p>

                                <WeatherDisplay
                                    :weather-data="weatherData"
                                    :weather-loading="weatherLoading"
                                    :weather-error="weatherError"
                                />

                                <!-- Local Time & Currency Card -->
                                <LocalInfoCard
                                    v-if="weatherData"
                                    :local-time="localTime"
                                    :local-currency="localCurrency"
                                />

                                <div
                                    v-if="placesError"
                                    class="mt-4 rounded bg-yellow-100 p-3 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
                                >
                                    <p>{{ placesError }}</p>
                                </div>

                                <!-- Favorite Error Display -->
                                <div v-if="favoritesError" class="mt-4 rounded bg-red-100 p-3 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <p class="text-sm">Error: {{ favoritesError }}</p>
                                </div>
                            </div>

                            <div class="lg:col-span-3">
                                <div ref="mapContainer" class="relative h-96 w-full rounded-lg shadow-md"></div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <Tabs v-model="activeTab" default-value="search" class="w-full">
                                <TabsList class="mb-4 grid w-full grid-cols-2">
                                    <TabsTrigger value="search">Search Results</TabsTrigger>
                                    <TabsTrigger value="favorites">Favorites ({{ favoritePlaces.length }})</TabsTrigger>
                                </TabsList>

                                <!-- Use SearchResults component here -->
                                <TabsContent value="search">
                                    <SearchResults
                                        :places="places"
                                        :places-loading="placesLoading"
                                        :places-error="placesError"
                                        :coordinates="coordinates"
                                        :is-favorite="isFavorite"
                                        @open-gallery="openGallery"
                                        @toggle-favorite="handleToggleFavorite"
                                    />
                                </TabsContent>

                                <!-- Use FavoritesList component here -->
                                <TabsContent value="favorites">
                                    <FavoritesList
                                        :favorite-places="favoritePlaces"
                                        :favorites-loading="favoritesLoading"
                                        :is-favorite="isFavorite"
                                        @open-gallery="openGallery"
                                        @toggle-favorite="handleToggleFavorite"
                                    />
                                </TabsContent>
                            </Tabs>
                        </div>
                    </div>
                </div>

                <!-- Gallery Modal -->
                <GalleryModal
                    v-model:isOpen="isGalleryOpen"
                    :place="galleryPlace"
                    :loading="galleryLoading"
                    :error="galleryError"
                />
            </div>
            <!-- Closes max-w-7xl -->
        </div>
        <!-- Closes py-12 div -->
    </AuthenticatedLayout>
</template>

<style>
.map-container {
    height: 500px; /* Or adjust as needed */
    width: 100%;
}

/* Style the geocoder container if necessary */
.geocoder-container .mapboxgl-ctrl-geocoder {
    width: 100%;
    max-width: none; /* Override default max-width if needed */
    font-size: 1rem; /* Adjust font size */
    box-shadow: none; /* Remove default shadow if desired */
    border: 1px solid hsl(var(--input)); /* Match input style */
    border-radius: var(--radius);
}

.geocoder-container .mapboxgl-ctrl-geocoder input[type='text'] {
    padding: 0.75rem; /* Adjust padding */
}

/* Ensure map controls are visible in dark mode if needed */
.mapboxgl-ctrl-attrib-inner,
.mapboxgl-ctrl-logo {
    /* Add styles for dark mode visibility if necessary */
}

/* You might need to adjust styles based on your specific UI library */
</style>
