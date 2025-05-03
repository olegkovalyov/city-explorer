<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3'; 
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import MapboxGeocoder from '@mapbox/mapbox-gl-geocoder'; 
import '@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css'; 

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

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

function initializeOrUpdateMap(centerCoords = null) { 
  if (!props.mapboxToken) {
    console.error("Mapbox token is missing. Cannot display map.");
    placesError.value = "Map configuration error."; 
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
            console.error("Geocoder container not found in DOM.");
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
                 'Accept': 'application/json', 
             }
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
            } catch (parseError) {
                errorMessage = `Failed to parse error response (Status: ${response.status}).`;
            }
            throw new Error(errorMessage);
        }
        const data = await response.json(); 
        places.value = data;

        if(places.value.length === 0) {
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
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            let errorMessage = `Weather API Error: ${response.status}`;
            try {
                const errorBody = await response.json();
                errorMessage = errorBody.message || JSON.stringify(errorBody);
            } catch (e) { /* Ignore parsing error, use status code */ }
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
    const name = 'XSRF-TOKEN=';
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return '';
}

onMounted(() => {
  initializeOrUpdateMap(); 
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">City Explorer Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                            <div class="lg:col-span-2">
                                 <h3 class="text-lg font-medium mb-4">Find a Location</h3>
                                <!-- Container for the external Mapbox Geocoder -->
                                <div ref="geocoderContainer" class="mb-4"></div>
                                <p class="text-xs text-muted-foreground">Enter an address or place name above.</p>

                                <div v-if="coordinates || weatherLoading || weatherError" class="mt-4 p-4 border rounded-lg dark:border-gray-700">
                                    <h4 class="text-md font-semibold mb-2">Current Weather</h4>
                                    <div v-if="weatherLoading" class="text-sm text-muted-foreground">Loading weather...</div>
                                    <div v-else-if="weatherError" class="text-sm text-red-600 dark:text-red-400">Error: {{ weatherError }}</div>
                                    <div v-else-if="weatherData" class="flex items-center space-x-4">
                                        <img v-if="weatherData.icon_url" :src="weatherData.icon_url" :alt="weatherData.description" class="w-12 h-12">
                                        <div>
                                            <p class="text-lg font-medium">{{ Math.round(weatherData.temperature) }}°C <span v-if="weatherData.feels_like" class="text-sm text-muted-foreground">(feels like {{ Math.round(weatherData.feels_like) }}°C)</span></p>
                                            <p class="text-sm capitalize text-muted-foreground">{{ weatherData.description }}</p>
                                            <p v-if="weatherData.city_name" class="text-xs text-muted-foreground">({{ weatherData.city_name }})</p>
                                        </div>
                                    </div>
                                    <div v-if="weatherData" class="mt-3 pt-3 border-t dark:border-gray-600 text-sm text-muted-foreground space-y-1">
                                        <p v-if="weatherData.humidity !== null">Humidity: {{ weatherData.humidity }}%</p>
                                        <p v-if="weatherData.wind_speed !== null">Wind: {{ weatherData.wind_speed.toFixed(1) }} m/s</p>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">Select a location to see the weather.</div>
                                </div>

                                <div v-if="placesError" class="mt-4 p-3 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    <p>{{ placesError }}</p>
                                </div>

                            </div>

                            <div class="lg:col-span-3">
                                <div ref="mapContainer" class="h-96 w-full rounded-lg shadow-md relative"></div>
                            </div>
                        </div>

                        <div v-if="coordinates && !placesError && !placesLoading" class="mt-8">
                            <h3 class="text-2xl font-semibold leading-tight text-gray-800 mb-4">Nearby Places</h3>

                            <div v-if="placesLoading" class="text-center py-6 text-muted-foreground">
                                Loading places...
                            </div>

                            <div v-if="placesError && !placesLoading" class="p-4 text-sm text-yellow-700 bg-yellow-100 rounded-md w-full text-center">
                               {{ placesError }}
                            </div>

                            <div v-if="!placesLoading && !placesError && places.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <Card v-for="place in places" :key="place.id">
                                    <CardHeader class="p-0">
                                        <img
                                            v-if="place.photo_url"
                                            :src="place.photo_url"
                                            :alt="place.name"
                                            class="h-40 w-full object-cover rounded-t-lg"
                                            loading="lazy"
                                            @error="$event.target.style.display='none'" />
                                        <div v-else class="h-40 w-full bg-muted rounded-t-lg flex items-center justify-center text-muted-foreground">
                                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-image-off'><path d='M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z'/><path d='M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z'/><path d='M21 15l-5-5L7 21'/><path d='m2 2 20 20'/><path d='M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z'/></svg>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="p-4">
                                        <CardTitle class="text-lg mb-1 truncate">{{ place.name }}</CardTitle>
                                         <Badge variant="secondary" class="mb-2">
                                             <img v-if="place.category_icon" :src="place.category_icon" :alt="place.category" class="w-4 h-4 mr-1"/>
                                            {{ place.category }}
                                         </Badge>
                                        <p v-if="place.address" class="text-sm text-muted-foreground truncate">
                                            {{ place.address }}
                                        </p>
                                        <p v-else class="text-sm text-muted-foreground italic">
                                            Address not available
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
