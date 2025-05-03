import { ref, onMounted, onUnmounted, watch } from 'vue';
import mapboxgl from 'mapbox-gl';
import MapboxGeocoder from '@mapbox/mapbox-gl-geocoder';

export function useMapbox(mapboxToken, mapContainerRef, geocoderContainerRef) {
    const coordinates = ref(null); // State for coordinates
    const mapInstance = ref(null);
    let geocoder = null;

    const initializeMap = (centerCoords = null) => {
        if (!mapboxToken || !mapContainerRef.value) {
            console.error('Mapbox token or map container missing.');
            return;
        }

        mapboxgl.accessToken = mapboxToken;

        const initialCenter = centerCoords || [-74.5, 40]; // Default center
        const initialZoom = centerCoords ? 12 : 11; // Zoom in if specific coords provided, default zoom increased to 11

        mapInstance.value = new mapboxgl.Map({
            container: mapContainerRef.value,
            style: 'mapbox://styles/mapbox/streets-v11',
            center: initialCenter,
            zoom: initialZoom
        });

        // Initialize Geocoder only if container exists
        if (geocoderContainerRef.value) {
            geocoder = new MapboxGeocoder({
                accessToken: mapboxgl.accessToken,
                mapboxgl: mapboxgl,
                marker: true // Show marker for geocoded result
            });

            // Mount geocoder
            // Use a timeout to ensure the container is ready
            setTimeout(() => {
                if (geocoderContainerRef.value && geocoder) {
                    try {
                         // Check if the element is already mounted
                        if (!geocoderContainerRef.value.contains(geocoder.onAdd(mapInstance.value))) {
                             geocoderContainerRef.value.appendChild(geocoder.onAdd(mapInstance.value));
                        }
                    } catch (error) {
                        console.error("Error mounting geocoder:", error);
                    }
                }
            }, 0);


            // Handle geocoder result
            geocoder.on('result', (e) => {
                const newCoords = e.result.geometry.coordinates;
                coordinates.value = { longitude: newCoords[0], latitude: newCoords[1] };
                // Optionally fly map to result
                // mapInstance.value.flyTo({ center: newCoords, zoom: 14 });
            });
        }

        mapInstance.value.on('load', () => {
             // If initial coords were provided explicitly (e.g., from a saved state)
            if (centerCoords) {
                coordinates.value = { longitude: centerCoords[0], latitude: centerCoords[1] };
                 new mapboxgl.Marker()
                    .setLngLat(centerCoords)
                    .addTo(mapInstance.value);
            }
            // Attempt to get user's location if no centerCoords provided
            else if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(position => {
                    const userCoords = [position.coords.longitude, position.coords.latitude];
                    mapInstance.value.setCenter(userCoords);
                    mapInstance.value.setZoom(12);
                    // Set coordinates ONLY if geolocation is successful
                    coordinates.value = { longitude: userCoords[0], latitude: userCoords[1] };
                    // Optionally add a marker for user location
                     new mapboxgl.Marker({ color: 'red' }).setLngLat(userCoords).addTo(mapInstance.value);
                }, (err) => {
                    console.warn(`Geolocation error: ${err.message}. Map centered on default view. No location set.`);
                     // If geolocation fails or is denied, use default view (already set)
                     // DO NOT set coordinates based on default center here
                    // coordinates.value = { longitude: initialCenter[0], latitude: initialCenter[1] };
                });
            } else {
                 // Set coordinates based on default center if geolocation not supported
                 // DO NOT set coordinates based on default center here
                // coordinates.value = { longitude: initialCenter[0], latitude: initialCenter[1] };
            }
        });

        mapInstance.value.on('error', (e) => {
            console.error('Mapbox GL error:', e.error);
        });
    };

    // Initialize map when the component mounts and container refs are available
    onMounted(() => {
        // Watch for refs becoming available if needed, though typically onMounted is sufficient
        // Use nextTick if initialization depends on complex DOM rendering
        // nextTick(() => {
            initializeMap();
        // });
    });

    // Cleanup map resources on unmount
    onUnmounted(() => {
        if (geocoder && geocoderContainerRef.value && geocoder.onRemove) {
             try {
                // Check if the geocoder element is still a child before removing
                const geocoderElement = geocoder.container;
                if (geocoderElement && geocoderContainerRef.value.contains(geocoderElement)) {
                    geocoderContainerRef.value.removeChild(geocoderElement);
                }
             } catch (error) {
                console.error("Error removing geocoder element:", error);
             }
             // Ensure geocoder internal cleanup runs if possible
             if (typeof geocoder.onRemove === 'function') {
                 geocoder.onRemove();
             }
        }
        if (mapInstance.value) {
            mapInstance.value.remove();
            mapInstance.value = null;
        }
        console.log('Mapbox resources cleaned up.');
    });

    // Function to update map center (can be called from parent)
    const setMapCenter = (coords) => {
        if (mapInstance.value && coords && coords.longitude && coords.latitude) {
            const newCenter = [coords.longitude, coords.latitude];
            mapInstance.value.flyTo({ center: newCenter, zoom: 14 });
             // Optionally add/update a marker
            // Consider managing markers if multiple are needed
        }
    };

    // Watch for external coordinate changes to update map center
    // This might be useful if coordinates are set programmatically elsewhere
    // watch(coordinates, (newCoords) => {
    //    setMapCenter(newCoords);
    // }, { deep: true });

    return {
        coordinates,
        mapInstance, // Expose map instance if needed for advanced interactions
        initializeMap, // Expose if re-initialization needed
        setMapCenter // Expose function to control map center
    };
}
