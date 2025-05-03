<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css';
import 'mapbox-gl/dist/mapbox-gl.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Star } from 'lucide-vue-next';

import { useWeather } from '@/composables/useWeather.js';
import { usePlaces } from '@/composables/usePlaces.js';
import { useFavorites } from '@/composables/useFavorites.js';
import { useGalleryModal } from '@/composables/useGalleryModal.js';
import { useMapbox } from '@/composables/useMapbox.js';

const props = defineProps({
    mapboxToken: String,
});

const mapContainer = ref(null);
const geocoderContainer = ref(null);

// Ref for controlling the active tab (Search Results / Favorites)
const activeTab = ref('search');

const {
    coordinates,
} = useMapbox(props.mapboxToken, mapContainer, geocoderContainer);

const {
    favoritePlaces,
    favoritesLoading,
    favoritesError,
    isFavorite,
    fetchFavorites,
    handleToggleFavorite
} = useFavorites();

const {
    weatherData,
    weatherLoading,
    weatherError,
    getWeather,
    localTime,
    localCurrency
} = useWeather();

const {
    places,
    placesLoading,
    placesError,
    getPlaces
} = usePlaces();

const {
    galleryPlace,
    isGalleryOpen,
    galleryLoading,
    galleryError,
    openGallery,
    closeGallery,
} = useGalleryModal();

onMounted(() => {
    fetchFavorites();
});

watch(coordinates, (newCoords) => {
    if (newCoords) {
        getWeather(newCoords.latitude, newCoords.longitude);
        getPlaces(newCoords.latitude, newCoords.longitude);
    }
}, { immediate: true });

onUnmounted(() => {
});

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
                                        <p v-if="localTime">Local Time: {{ localTime }}</p>
                                        <p v-if="localCurrency">Currency: {{ localCurrency }}</p>
                                        <p v-if="weatherData.humidity !== null">Humidity: {{ weatherData.humidity }}%</p>
                                        <p v-if="weatherData.wind_speed !== null">Wind: {{ weatherData.wind_speed.toFixed(1) }} m/s</p>
                                    </div>
                                    <div v-else-if="!weatherLoading && !weatherError" class="text-sm text-muted-foreground">
                                        Weather data not available for this location.
                                    </div>
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
                                            <div v-if="placesLoading" class="text-center text-muted-foreground">Loading places...</div>
                                            <div v-else-if="placesError" class="text-center text-red-600 dark:text-red-400">{{ placesError }}</div>
                                            <div v-else-if="places.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
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
                                                        @click.stop="handleToggleFavorite(place)"
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
                                                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3"
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
                                                        @click.stop="handleToggleFavorite(place)"
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
                    :open="isGalleryOpen"
                    @update:open="
                        (isOpen) => {
                            if (!isOpen) closeGallery();
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
                        <div class="relative flex-1 flex items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-900 p-2">
                            <!-- Scrollable container for the grid -->
                            <div class="w-full h-full overflow-y-auto p-4">
                                <!-- Loading State -->
                                <div v-if="galleryLoading" class="flex items-center justify-center h-full">
                                    <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-muted-foreground">Loading details...</span>
                                </div>

                                <!-- Error State -->
                                <div v-else-if="galleryError" class="text-center text-destructive p-4">
                                    <p><strong>Error:</strong> {{ galleryError }}</p>
                                </div>

                                <!-- Image Grid Display State -->
                                <div v-else-if="galleryPlace && galleryPlace.photos && galleryPlace.photos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                    <div v-for="(photoUrl, index) in galleryPlace.photos" :key="photoUrl + '-' + index" class="aspect-square overflow-hidden rounded-md bg-muted">
                                        <img
                                            :src="photoUrl"
                                            :alt="`Photo ${index + 1} for ${galleryPlace.name}`"
                                            class="h-full w-full object-cover transition-transform duration-300 hover:scale-105 cursor-pointer"
                                            loading="lazy"
                                        />
                                    </div>
                                </div>

                                <!-- No Photos State -->
                                <div v-else class="flex items-center justify-center h-full text-center text-muted-foreground">
                                    {{ galleryError && galleryError.includes('No photos found') ? galleryError : 'No photos available for this place.' }}
                                </div>
                            </div>
                        </div>
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
.mapboxgl-ctrl-attrib-inner, .mapboxgl-ctrl-logo {
    /* Add styles for dark mode visibility if necessary */
}

/* You might need to adjust styles based on your specific UI library */
</style>
