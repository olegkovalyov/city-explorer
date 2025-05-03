import { ref } from 'vue';
// Assuming axios is globally available or imported where needed in the project
// If not, you might need to import it here: import axios from 'axios';

export function useGalleryModal() {
    // State for gallery modal
    const galleryPlace = ref(null); // Store the whole place object for the gallery
    const isGalleryOpen = ref(false);

    // State for Gallery Loading/Error
    const galleryLoading = ref(false);
    const galleryError = ref(null);

    // Function to fetch place details (copied from Dashboard)
    async function fetchPlaceDetails(fsqId) {
        if (!fsqId) {
            console.error('fetchPlaceDetails: fsqId is missing');
            throw new Error('Cannot fetch details without a Foursquare ID.');
        }
        try {
            // Use axios as it's likely imported elsewhere
            const response = await axios.get(`/api/places/${fsqId}`);
            // Ensure photos is always an array, even if null/missing in response
            if (response.data && !Array.isArray(response.data.photos)) {
                response.data.photos = [];
            }
            return response.data; // Return the detailed place object
        } catch (err) {
            console.error('Error fetching place details via backend:', err);
            const message = err.response?.data?.message || err.response?.data?.error || err.message || 'Failed to load place details.';
            throw new Error(message);
        }
    }

    // Function to open gallery (copied from Dashboard)
    const openGallery = async (place) => {
        if (!place) return; // Do nothing if place is null/undefined

        galleryLoading.value = false; // Reset states
        galleryError.value = null;

        galleryPlace.value = place; // Set initial place data
        isGalleryOpen.value = true; // Open modal immediately

        // Determine if fetching is needed (no 'photos' array or empty 'photos' array, AND has fsq_id or id)
        const idToFetch = place.fsq_id || place.id;
        const needsFetching = (!Array.isArray(place.photos) || place.photos.length === 0) && idToFetch;

        if (needsFetching) {
            galleryLoading.value = true;
            try {
                // Fetch full details from the backend
                const detailedPlace = await fetchPlaceDetails(idToFetch);
                // Update galleryPlace with the complete data
                galleryPlace.value = detailedPlace;
                // If after fetching, there are still no photos, set a message (might be an error or just no photos)
                if (!galleryPlace.value.photos || galleryPlace.value.photos.length === 0) {
                    galleryError.value = 'No photos found for this place.'; // Use error state to display message
                    console.warn(`No photos found for ${place.name} after fetching details.`);
                }
            } catch (err) {
                console.error('Failed to fetch details for gallery:', err);
                // Keep the modal open, but show the error message
                galleryError.value = err.message || 'Could not load place details.';
            } finally {
                galleryLoading.value = false; // Ensure loading indicator is turned off
            }
        } else {
            // Photos are already present (likely from search results)
            if (!place.photos || place.photos.length === 0) {
                // Should not happen if needsFetching is false, but as a safeguard
                galleryError.value = 'No photos available for this place.';
            }
        }
    };
    const closeGallery = () => {
        isGalleryOpen.value = false;
    };

    return {
        galleryPlace,
        isGalleryOpen,
        galleryLoading,
        galleryError,
        openGallery,
        closeGallery,
    };
}
