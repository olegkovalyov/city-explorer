<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import MapboxGeocoder from '@mapbox/mapbox-gl-geocoder';
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Star } from 'lucide-vue-next';

const props = defineProps({
    mapboxToken: String,
});

const coordinates = ref(null);
const places = ref([]);
const placesLoading = ref(false);
const placesError = ref(null);
const weatherData = ref(null);
const weatherLoading = ref(false);
const weatherError = ref(null);

const mapContainer = ref(null);
const mapInstance = ref(null);
const geocoderContainer = ref(null);

// State for gallery modal
const galleryPlace = ref(null); // Store the whole place object for the gallery

// --- State for Favorites ---
const favoritePlaces = ref([]);
const favoritesLoading = ref(false);
const favoritesError = ref(null);
const activeTab = ref('search'); // Default tab

function initializeOrUpdateMap(centerCoords = null) {
    if (!props.mapboxToken) {
        console.error('Mapbox token is missing. Cannot display map.');
        placesError.value = 'Map configuration error.';
        return;
    }

    if (!mapInstance.value) {
        mapboxgl.accessToken = props.mapboxToken;
        mapInstance.value = new mapboxgl.Map({
            container: mapContainer.value,
            style: 'mapbox://styles/mapbox/streets-v12',
            center: centerCoords || [-74.5, 40],
            zoom: centerCoords ? 13 : 9,
        });

        const geocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            marker: true,
            placeholder: 'Search for places or addresses',
        });

        nextTick(() => {
            if (geocoderContainer.value) {
                while (geocoderContainer.value.firstChild) {
                    geocoderContainer.value.removeChild(geocoderContainer.value.firstChild);
                }
                geocoderContainer.value.appendChild(geocoder.onAdd(mapInstance.value));
            } else {
                console.error('Geocoder container not found in DOM.');
            }
        });

        geocoder.on('result', (e) => {
            console.log('Geocoder result:', e.result);
            const newCoords = e.result.geometry.coordinates;
            coordinates.value = { lat: newCoords[1], lng: newCoords[0] };
            mapInstance.value.flyTo({ center: newCoords, zoom: 14 });

            places.value = [];
            placesError.value = null;
            weatherData.value = null;
            weatherError.value = null;

            getPlaces(newCoords[1], newCoords[0]);
            getWeather(newCoords[1], newCoords[0]);
        });

        geocoder.on('clear', () => {
            places.value = [];
            placesError.value = null;
            coordinates.value = null;
            weatherData.value = null;
            weatherError.value = null;
        });

        mapInstance.value.on('error', (e) => {
            console.error('Mapbox GL error:', e.error);
            placesError.value = 'Failed to load map.';
        });
    } else if (centerCoords) {
        mapInstance.value.flyTo({ center: centerCoords, zoom: 14 });
    }
}

async function getPlaces(lat, lng) {
    placesLoading.value = true;
    placesError.value = null;
    places.value = [];
    const csrfToken = getCsrfToken();

    try {
        const response = await fetch(`/api/places?latitude=${lat}&longitude=${lng}`, {
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            let errorMessage = `Failed to fetch places. Status: ${response.status}`;
            try {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const errorData = await response.json();
                    errorMessage = errorData.message || JSON.stringify(errorData);
                } else {
                    const errorText = await response.text();
                    errorMessage = `Server returned non-JSON response (Status: ${response.status}). Response: ${errorText.substring(0, 200)}...`;
                }
                // eslint-disable-next-line @typescript-eslint/no-unused-vars
            } catch (parseError) {
                errorMessage = `Failed to parse error response (Status: ${response.status}).`;
            }
            throw new Error(errorMessage);
        }
        const data = await response.json();
        places.value = data.places || []; // Correctly assign the array, default to empty if missing

        if (!places.value || places.value.length === 0) { 
            placesError.value = 'No interesting places found nearby.';
        }
    } catch (err) {
        console.error('Fetching places error:', err);
        placesError.value = err.message || 'Failed to fetch places.';
    } finally {
        placesLoading.value = false;
    }
}

async function getWeather(lat, lng) {
    weatherLoading.value = true;
    weatherError.value = null;
    weatherData.value = null;
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
            let errorMessage = `Weather API Error: ${response.status}`;
            try {
                const errorBody = await response.json();
                errorMessage = errorBody.message || JSON.stringify(errorBody);
                // eslint-disable-next-line @typescript-eslint/no-unused-vars
            } catch (e) {
                /* Ignore parsing error, use status code */
            }
            throw new Error(errorMessage);
        }

        weatherData.value = await response.json();
    } catch (err) {
        console.error('Weather fetching error:', err);
        weatherError.value = err.message || 'Failed to fetch weather data.';
    } finally {
        weatherLoading.value = false;
    }
}

function getCsrfToken() {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];
    return token ? decodeURIComponent(token) : null;
}

// --- Country to Currency Mapping (Simplified) ---
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
    RU: 'RUB (₽)',
    CH: 'CHF (Fr)',
    SE: 'SEK (kr)',
    NO: 'NOK (kr)',
    DK: 'DKK (kr)',
    // Add more common ones as needed
};

// --- Computed Properties ---
const localTime = computed(() => {
    if (!weatherData.value || weatherData.value.timezone_offset_seconds === null) {
        return 'N/A';
    }
    try {
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

const localCurrency = computed(() => {
    if (!weatherData.value || !weatherData.value.country_code) {
        return 'N/A';
    }
    return countryCurrencyMap[weatherData.value.country_code] || `Unknown (${weatherData.value.country_code})`;
});

// --- Computed properties and helpers for Favorites ---
const favoritePlaceIds = computed(() => {
    // Use fsq_id as the unique identifier from our backend
    return new Set(favoritePlaces.value.map((fp) => fp.fsq_id));
});

// Helper function to check if a place is favorited
function isFavorite(place) {
    // Check against fsq_id if available (from favorites list), otherwise use id (from search)
    const idToCheck = place.fsq_id || place.id;
    return favoritePlaceIds.value.has(idToCheck);
}

// --- Fetch Favorites Logic ---
async function fetchFavorites() {
    favoritesLoading.value = true;
    favoritesError.value = null;
    const csrfToken = getCsrfToken(); // Ensure you have this helper function

    if (!csrfToken) {
        console.error('CSRF Token not found.');
        favoritesError.value = 'Authentication error. Please refresh the page.';
        favoritesLoading.value = false;
        return;
    }

    try {
        const response = await fetch('/api/favorite-places', {
            credentials: 'include', // Important for sending cookies (like session/XSRF)
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Required by Laravel
                'X-XSRF-TOKEN': csrfToken, // CSRF Protection
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Unknown error' }));
            throw new Error(`Failed to fetch favorites: ${response.status} - ${errorData.message}`);
        }

        favoritePlaces.value = await response.json();
    } catch (err) {
        console.error('Favorites fetching error:', err);
        favoritesError.value = err.message || 'Failed to load favorites.';
    } finally {
        favoritesLoading.value = false;
    }
}

// --- Toggle Favorite Logic ---
async function toggleFavorite(place) {
    const csrfToken = getCsrfToken();
    // Ensure we consistently use fsq_id for API calls and checking
    const placeId = place.fsq_id || place.id;

    if (!placeId) {
        console.error('Cannot toggle favorite: Missing place ID');
        favoritesError.value = 'Cannot favorite this item (missing ID).';
        return;
    }
    if (!csrfToken) {
        console.error('Cannot toggle favorite: Missing CSRF token');
        favoritesError.value = 'Authentication error. Please refresh.';
        return;
    }

    const currentlyFavorite = isFavorite(place);
    const method = currentlyFavorite ? 'DELETE' : 'POST';
    const url = currentlyFavorite ? `/api/favorite-places/${placeId}` : '/api/favorite-places';

    // Prepare data needed only for POST (add)
    let placeDataForApi = null;
    if (!currentlyFavorite) {
        placeDataForApi = {
            fsq_id: placeId,
            name: place.name,
            address: place.location?.formatted_address || place.address || null, // Prefer FSQ 'location' if available
            latitude: place.geocodes?.main?.latitude || place.latitude || null,
            longitude: place.geocodes?.main?.longitude || place.longitude || null,
            // Get photo: Use 'prefix'+'original'+'suffix' if available (FSQ search), else stored photo_url (favorites)
            photo_url:
                place.photos && place.photos.length > 0 && place.photos[0].prefix
                    ? `${place.photos[0].prefix}original${place.photos[0].suffix}`
                    : place.photos && place.photos.length > 0
                      ? place.photos[0] // Handle old structure if needed
                      : place.photo_url || null,
            // Get category: Use FSQ 'categories' array if available, else stored category
            category: place.categories?.[0]?.name || place.category || null,
            category_icon: place.categories?.[0]?.icon
                ? `${place.categories[0].icon.prefix}bg_64${place.categories[0].icon.suffix}`
                : place.category_icon || null,
        };
    }

    // --- Optimistic UI Update ---
    const originalFavorites = [...favoritePlaces.value]; // Keep backup for rollback
    let addedPlace = null; // Store the temporary representation
    if (currentlyFavorite) {
        favoritePlaces.value = favoritePlaces.value.filter((fp) => fp.fsq_id !== placeId);
    } else if (placeDataForApi) {
        // Add a temporary representation using the prepared data
        // Important: Ensure this temporary object has `fsq_id`!
        addedPlace = { ...placeDataForApi, id: null, user_id: null, created_at: null, updated_at: null }; // Match DB structure somewhat
        favoritePlaces.value.unshift(addedPlace); // Add to beginning for immediate visibility
    }
    favoritesError.value = null; // Clear previous error
    // --- End Optimistic Update ---

    try {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken,
            Accept: 'application/json',
        };
        let body = null;

        if (method === 'POST' && placeDataForApi) {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(placeDataForApi);
        }

        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: headers,
            body: body,
        });

        if (!response.ok && response.status !== 204) {
            // 204 No Content is OK for DELETE
            const errorData = await response.json().catch(() => ({ message: 'Operation failed' }));
            throw new Error(`API Error ${response.status}: ${errorData.message}`);
        }

        // --- Update state based on successful API response ---
        if (method === 'POST') {
            const addedFavoriteFromApi = await response.json();
            // If we optimistically added, replace the temporary object with the real one from the API
            if (addedPlace) {
                const index = favoritePlaces.value.findIndex((p) => p.fsq_id === addedPlace.fsq_id && p.id === null); // Find the temporary one
                if (index > -1) {
                    favoritePlaces.value.splice(index, 1, addedFavoriteFromApi);
                } else {
                    // If not found (e.g., user switched tabs fast?), just add the API result if not present
                    if (!favoritePlaceIds.value.has(addedFavoriteFromApi.fsq_id)) {
                        favoritePlaces.value.unshift(addedFavoriteFromApi);
                    }
                }
            } else if (!favoritePlaceIds.value.has(addedFavoriteFromApi.fsq_id)) {
                // If not optimistically added (shouldn't happen with current logic, but safer)
                favoritePlaces.value.unshift(addedFavoriteFromApi);
            }
        }
        // DELETE is already handled optimistically
        // --- End API Response Handling ---
    } catch (err) {
        console.error('Toggle favorite error:', err);
        // --- Rollback Optimistic Update ---
        favoritePlaces.value = originalFavorites;
        // --- End Rollback ---
        favoritesError.value = `Failed to update favorite: ${err.message}`;
    }
}

// --- Function to open gallery ---
function openGallery(place) {
    if (place) {
        galleryPlace.value = place;
    }
}

// --- Lifecycle Hooks ---
onMounted(() => {
    initializeOrUpdateMap();
    fetchFavorites(); // Fetch favorites when component mounts
});

onUnmounted(() => {
    if (mapInstance.value) {
        mapInstance.value.remove();
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">City Explorer Dashboard</h2>
        </template>

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

                                <div v-if="coordinates || weatherLoading || weatherError" class="mt-4 rounded-lg border p-4 dark:border-gray-700">
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
                                                {{ Math.round(weatherData.temperature) }}°C
                                                <span v-if="weatherData.feels_like" class="text-sm text-muted-foreground"
                                                    >(feels like {{ Math.round(weatherData.feels_like) }}°C)</span
                                                >
                                            </p>
                                            <p class="text-sm capitalize text-muted-foreground">{{ weatherData.description }}</p>
                                            <p v-if="weatherData.city_name" class="text-xs text-muted-foreground">({{ weatherData.city_name }})</p>
                                        </div>
                                    </div>
                                    <div v-if="weatherData" class="mt-3 space-y-1 border-t pt-3 text-sm text-muted-foreground dark:border-gray-600">
                                        <p v-if="weatherData.humidity !== null">Humidity: {{ weatherData.humidity }}%</p>
                                        <p v-if="weatherData.wind_speed !== null">Wind: {{ weatherData.wind_speed.toFixed(1) }} m/s</p>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">Select a location to see the weather.</div>
                                </div>

                                <!-- Local Time & Currency Card -->
                                <Card v-if="weatherData" class="mt-4">
                                    <CardHeader>
                                        <CardTitle class="text-md">Local Info</CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-2 text-sm">
                                        <p><span class="font-medium">Time:</span> {{ localTime }}</p>
                                        <p><span class="font-medium">Currency:</span> {{ localCurrency }}</p>
                                    </CardContent>
                                </Card>

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

                                <!-- Search Results Tab Content -->
                                <TabsContent value="search">
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Nearby Places</CardTitle>
                                            <CardDescription>Places discovered around the selected location.</CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <!-- Conditional rendering for Search Results -->
                                            <div v-if="!coordinates && !placesLoading" class="py-6 text-center text-muted-foreground">
                                                Select a location to search for nearby places.
                                            </div>
                                            <div v-else-if="placesLoading" class="py-6 text-center text-muted-foreground">
                                                Loading nearby places...
                                            </div>
                                            <div v-else-if="placesError" class="py-6 text-center text-red-600 dark:text-red-400">
                                                Error loading places: {{ placesError }}
                                            </div>
                                            <div
                                                v-else-if="places.length > 0"
                                                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3"
                                            >
                                                <!-- Existing Search Results Grid -->
                                                <div v-for="place in places" :key="place.id" class="relative">
                                                    <Card
                                                        class="h-full cursor-pointer transition-shadow duration-200 hover:shadow-lg"
                                                        @click="openGallery(place)"
                                                    >
                                                        <CardHeader class="p-0">
                                                            <img
                                                                v-if="place.photos && place.photos.length > 0"
                                                                :src="place.photos[0]"
                                                                :alt="place.name"
                                                                class="h-40 w-full rounded-t-lg object-cover"
                                                                loading="lazy"
                                                                @error="
                                                                    $event.target.src =
                                                                        'data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2724%27 height=%2724%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 class=%27lucide lucide-image-off%27%3e%3cpath d=%27M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z%27/%3e%3cpath d=%27M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z%27/%3e%3cpath d=%27M21 15l-5-5L7 21%27/%3e%3cpath d=%27m2 2 20 20%27/%3e%3cpath d=%27M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z%27/%3e%3c/svg%3e';
                                                                    $event.target.style.objectFit = 'contain';
                                                                    $event.target.style.padding = '1rem';
                                                                "
                                                            />
                                                            <div
                                                                v-else
                                                                class="flex h-40 w-full items-center justify-center rounded-t-lg bg-muted text-muted-foreground"
                                                            >
                                                                <svg
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    width="24"
                                                                    height="24"
                                                                    viewBox="0 0 24 24"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    stroke-width="2"
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    class="lucide lucide-image-off"
                                                                >
                                                                    <path
                                                                        d="M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z"
                                                                    />
                                                                    <path
                                                                        d="M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z"
                                                                    />
                                                                    <path d="M21 15l-5-5L7 21" />
                                                                    <path d="m2 2 20 20" />
                                                                    <path
                                                                        d="M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z"
                                                                    />
                                                                </svg>
                                                            </div>
                                                        </CardHeader>
                                                        <CardContent class="p-4">
                                                            <CardTitle class="mb-1 truncate text-lg">{{ place.name }}</CardTitle>
                                                            <Badge variant="secondary" class="mb-2"
                                                                ><img
                                                                    v-if="place.category_icon"
                                                                    :src="place.category_icon"
                                                                    :alt="place.category"
                                                                    class="mr-1 h-4 w-4"
                                                                />{{ place.category || place.categories?.[0]?.name || 'N/A' }}
                                                            </Badge>
                                                            <p v-if="place.address" class="truncate text-sm text-muted-foreground">
                                                                {{ place.address }}
                                                            </p>
                                                            <p v-else class="text-sm italic text-muted-foreground">Address not available</p>
                                                        </CardContent>
                                                    </Card>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="absolute right-2 top-2 z-10 h-8 w-8 rounded-full bg-black/30 text-white hover:bg-black/50"
                                                        @click.stop="toggleFavorite(place)"
                                                        :title="isFavorite(place) ? 'Remove from Favorites' : 'Add to Favorites'"
                                                    >
                                                        <Star :fill="isFavorite(place) ? 'currentColor' : 'none'" class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                            <div v-else-if="coordinates" class="py-6 text-center text-muted-foreground">
                                                No nearby places found for this location.
                                            </div>
                                        </CardContent>
                                    </Card>
                                </TabsContent>

                                <!-- Favorites Tab Content -->
                                <TabsContent value="favorites">
                                    <Card>
                                        <CardHeader>
                                            <CardTitle>Favorite Places</CardTitle>
                                            <CardDescription>Your saved places.</CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <!-- Conditional rendering for Favorites -->
                                            <div v-if="favoritesLoading" class="py-6 text-center text-muted-foreground">Loading favorites...</div>
                                            <div
                                                v-else-if="favoritePlaces.length > 0"
                                                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3"
                                            >
                                                <!-- Existing Favorites Grid -->
                                                <div v-for="place in favoritePlaces" :key="place.fsq_id" class="relative">
                                                    <Card
                                                        class="h-full cursor-pointer transition-shadow duration-200 hover:shadow-lg"
                                                        @click="openGallery(place)"
                                                    >
                                                        <CardHeader class="p-0">
                                                            <img
                                                                v-if="place.photo_url"
                                                                :src="place.photo_url"
                                                                :alt="place.name"
                                                                class="h-40 w-full rounded-t-lg object-cover"
                                                                loading="lazy"
                                                                @error="
                                                                    $event.target.src =
                                                                        'data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2724%27 height=%2724%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 class=%27lucide lucide-image-off%27%3e%3cpath d=%27M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z%27/%3e%3cpath d=%27M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z%27/%3e%3cpath d=%27M21 15l-5-5L7 21%27/%3e%3cpath d=%27m2 2 20 20%27/%3e%3cpath d=%27M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z%27/%3e%3c/svg%3e';
                                                                    $event.target.style.objectFit = 'contain';
                                                                    $event.target.style.padding = '1rem';
                                                                "
                                                            />
                                                            <div
                                                                v-else
                                                                class="flex h-40 w-full items-center justify-center rounded-t-lg bg-muted text-muted-foreground"
                                                            >
                                                                <svg
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    width="24"
                                                                    height="24"
                                                                    viewBox="0 0 24 24"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    stroke-width="2"
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    class="lucide lucide-image-off"
                                                                >
                                                                    <path
                                                                        d="M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z"
                                                                    />
                                                                    <path
                                                                        d="M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z"
                                                                    />
                                                                    <path d="M21 15l-5-5L7 21" />
                                                                    <path d="m2 2 20 20" />
                                                                    <path
                                                                        d="M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z"
                                                                    />
                                                                </svg>
                                                            </div>
                                                        </CardHeader>
                                                        <CardContent class="p-4">
                                                            <CardTitle class="mb-1 truncate text-lg">{{ place.name }}</CardTitle>
                                                            <Badge variant="secondary" class="mb-2"
                                                                ><img
                                                                    v-if="place.category_icon"
                                                                    :src="place.category_icon"
                                                                    :alt="place.category"
                                                                    class="mr-1 h-4 w-4"
                                                                />{{ place.category || 'N/A' }}
                                                            </Badge>
                                                            <p v-if="place.address" class="truncate text-sm text-muted-foreground">
                                                                {{ place.address }}
                                                            </p>
                                                            <p v-else class="text-sm italic text-muted-foreground">Address not available</p>
                                                        </CardContent>
                                                    </Card>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="absolute right-2 top-2 z-10 h-8 w-8 rounded-full bg-black/30 text-white hover:bg-black/50"
                                                        @click.stop="toggleFavorite(place)"
                                                        :title="isFavorite(place) ? 'Remove from Favorites' : 'Add to Favorites'"
                                                    >
                                                        <Star :fill="isFavorite(place) ? 'currentColor' : 'none'" class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                            <div v-else class="py-6 text-center text-muted-foreground">
                                                You haven't added any favorite places yet.
                                            </div>
                                        </CardContent>
                                    </Card>
                                </TabsContent>
                            </Tabs>
                        </div>
                    </div>
                </div>

                <!-- Gallery Modal -->
                <Dialog
                    v-if="galleryPlace"
                    :open="!!galleryPlace"
                    @update:open="
                        (isOpen) => {
                            if (!isOpen) galleryPlace = null;
                        }
                    "
                >
                    <DialogContent class="sm:max-w-[80%]">
                        <DialogHeader>
                            <DialogTitle>{{ galleryPlace.name }}</DialogTitle>
                            <DialogDescription>
                                {{ galleryPlace.address || galleryPlace.location?.formatted_address || 'Address not available' }}
                            </DialogDescription>
                        </DialogHeader>
                        <div
                            v-if="galleryPlace.photos && galleryPlace.photos.length > 0"
                            class="mt-4 grid max-h-[70vh] grid-cols-2 gap-4 overflow-y-auto md:grid-cols-3"
                        >
                            <img
                                v-for="(photo, index) in galleryPlace.photos"
                                :key="index"
                                :src="photo.prefix ? `${photo.prefix}original${photo.suffix}` : photo"
                                :alt="`${galleryPlace.name} photo ${index + 1}`"
                                class="aspect-square h-auto w-full cursor-pointer rounded-lg object-cover"
                                loading="lazy"
                                @error="$event.target.style.display = 'none'"
                                @click="
                                    () => {
                                        /* Potentially open full screen image viewer */
                                    }
                                "
                            />
                        </div>
                        <div v-else class="py-6 text-center text-muted-foreground">No photos available for this place.</div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">Close</Button>
                            </DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
            <!-- Closes max-w-7xl -->
        </div>
        <!-- Closes py-12 div -->
    </AuthenticatedLayout>
</template>
