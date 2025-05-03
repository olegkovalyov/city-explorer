import { getCsrfToken } from '@/utils/auth';
import { ref } from 'vue';

export function usePlaces() {
    const places = ref([]);
    const placesLoading = ref(false);
    const placesError = ref(null);

    async function getPlaces(lat, lng) {
        if (!lat || !lng) {
            placesError.value = 'Invalid coordinates provided for fetching places.';
            return;
        }
        placesLoading.value = true;
        placesError.value = null;
        places.value = []; // Reset previous places
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
                    const errorData = await response.json();
                    errorMessage += `: ${errorData.message || 'Unknown error'}`;
                    // eslint-disable-next-line @typescript-eslint/no-unused-vars
                } catch (e) {
                    // Ignore if response is not JSON
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();
            places.value = data.places || []; // Assign the array, default to empty if missing

            if (!places.value || places.value.length === 0) {
                // Set specific error message for no results, but don't overwrite network errors
                if (!placesError.value) {
                    placesError.value = 'No interesting places found nearby.';
                }
            }
        } catch (err) {
            placesError.value = err.message || 'Failed to load places.';
            console.error('Fetching places error:', err);
        } finally {
            placesLoading.value = false;
        }
    }

    return {
        places,
        placesLoading,
        placesError,
        getPlaces,
    };
}
