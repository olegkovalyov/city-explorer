import { ref, computed, onMounted, watch } from 'vue';
import { useToast } from '@/components/ui/toast/use-toast';
import axios from 'axios';

export function useFavorites() {
    const favoritePlaces = ref([]);
    const favoritesLoading = ref(false);
    const favoritesError = ref(null);
    const { toast } = useToast();

    // Map to store favorite place IDs for quick lookup
    const favoritePlaceIds = ref(new Set());

    // Function to check if a place is favorite
    function isFavorite(place) {
        const placeId = place?.fsq_id || place?.id; // Check both IDs
        return favoritePlaceIds.value.has(placeId);
    }

    // --- Fetch Favorites ---
    async function fetchFavorites() {
        favoritesLoading.value = true;
        favoritesError.value = null;

        try {
            const response = await axios.get('/api/favorite-places');
            favoritePlaces.value = response.data || [];
        } catch (err) {
            favoritesError.value = err.response?.data?.message || err.message || 'Failed to load favorites.';
            console.error('Fetching favorites error:', err);
        } finally {
            favoritesLoading.value = false;
        }
    }

    // --- Toggle Favorite ---
    async function handleToggleFavorite(place) {
        // Use place.id as fallback if fsq_id is missing
        const placeId = place?.fsq_id || place?.id;
        const placeName = place?.name;

        // Ensure both ID and Name are present and non-empty
        if (!placeId || typeof placeId !== 'string' || placeId.trim() === '' || 
            !placeName || typeof placeName !== 'string' || placeName.trim() === '') {
            console.error("Cannot toggle favorite: Missing or empty required place ID or name", { placeId, placeName, place });
            toast({ title: 'Error', description: 'Cannot add/remove favorite: Missing required place data.', variant: 'destructive'});
            return;
        }

        const currentlyFavorite = favoritePlaceIds.value.has(placeId);
        const originalFavorites = [...favoritePlaces.value]; // Keep a copy for rollback

        // Optimistic UI update
        if (currentlyFavorite) {
            favoritePlaces.value = favoritePlaces.value.filter(p => (p.fsq_id || p.id) !== placeId);
        } else {
            // Add a minimal representation or the full place object if needed
            favoritePlaces.value.push({ ...place, fsq_id: placeId });
        }

        // Determine method and URL
        const method = currentlyFavorite ? 'delete' : 'post';
        const url = currentlyFavorite ? `/api/favorite-places/${placeId}` : '/api/favorite-places';

        // Prepare body ONLY for POST requests
        const requestBody = !currentlyFavorite ? {
            fsq_id: placeId,
            name: placeName,
            address: place.address || '',
            category: place.category || place.categories?.[0]?.name || 'N/A',
            photo_url: place.photos?.[0] || place.photo_url || '',
            category_icon: place.category_icon || '',
        } : undefined;

        try {
            let response;
            if (method === 'post') {
                response = await axios.post(url, requestBody);
            } else {
                response = await axios.delete(url);
            }

            // Optional: Update local data with response from server if needed
            // const data = response.data;
            // console.log('Toggle success:', data);

            toast({ 
                title: 'Success',
                description: `Place ${currentlyFavorite ? 'removed from' : 'added to'} favorites.`,
            });

            // If the action was ADD, fetch the updated list to get the full object from DB
            if (!currentlyFavorite) {
                await fetchFavorites();
            }
        } catch (err) {
            // Use err.response for axios errors
            const errorMessage = err.response?.data?.message || err.message || `Failed to ${currentlyFavorite ? 'remove' : 'add'} favorite.`;
            console.error('Toggling favorite error:', err.response || err);
            favoritesError.value = errorMessage;

            // Rollback optimistic update
            favoritePlaces.value = originalFavorites;

            toast({ title: 'Error', description: favoritesError.value, variant: 'destructive'});
        }
    }

    // Watch for changes in favoritePlaces to update the Set
    watch(favoritePlaces, (newPlaces) => {
        // Rebuild the Set based on the current list of favorite places
        const newIdSet = new Set();
        if (Array.isArray(newPlaces)) {
            newPlaces.forEach(place => {
                const placeId = place?.fsq_id || place?.id; // Use the same logic to get ID
                if (placeId) {
                    newIdSet.add(placeId);
                }
            });
        }
        favoritePlaceIds.value = newIdSet;
        // console.log('Favorite IDs Set updated:', favoritePlaceIds.value); // Optional: for debugging
    }, { deep: true }); // Use deep watch if place objects might change internally

    return {
        favoritePlaces,
        favoritesLoading,
        favoritesError,
        favoritePlaceIds, // Expose computed property if needed outside
        isFavorite,
        fetchFavorites,
        handleToggleFavorite,
    };
}
