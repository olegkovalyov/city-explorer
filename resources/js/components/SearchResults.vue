<!-- resources/js/Components/SearchResults.vue -->
<script setup>
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Star } from 'lucide-vue-next';

defineProps({
    places: {
        type: Array,
        required: true,
    },
    placesLoading: {
        type: Boolean,
        default: false,
    },
    placesError: {
        type: String,
        default: null,
    },
    coordinates: { // Needed for the 'No results' message condition
        type: Object,
        default: null,
    },
    isFavorite: { // Function to check favorite status
        type: Function,
        required: true,
    },
});

// Define emits
const emit = defineEmits(['openGallery', 'toggleFavorite']);

// Placeholder image SVG data URL
const placeholderSvg = "data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='lucide lucide-image-off'%3e%3cpath d='M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z'/%3e%3cpath d='M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z'/%3e%3cpath d='M21 15l-5-5L7 21'/%3e%3cpath d='m2 2 20 20'/%3e%3cpath d='M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z'/%3e%3c/svg%3e";

const handleImageError = (event) => {
    event.target.src = placeholderSvg;
    event.target.style.objectFit = 'contain';
    event.target.style.padding = '1rem';
};

</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Nearby Places</CardTitle>
            <CardDescription>Places discovered around the selected location.</CardDescription>
        </CardHeader>
        <CardContent>
            <!-- Conditional rendering -->
            <div v-if="placesLoading" class="text-center text-muted-foreground">Loading places...</div>
            <div v-else-if="placesError" class="text-center text-red-600 dark:text-red-400">{{ placesError }}</div>
            <div v-else-if="places.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                <div v-for="place in places" :key="place.id || place.fsq_id" class="relative">
                    <Card
                        class="h-full cursor-pointer transition-shadow duration-200 hover:shadow-lg"
                        @click="$emit('openGallery', place)"
                    >
                        <CardHeader class="p-0">
                            <img
                                v-if="place.photos && place.photos.length > 0"
                                :src="place.photos[0]"
                                :alt="place.name"
                                class="h-40 w-full rounded-t-lg object-cover"
                                loading="lazy"
                                @error="handleImageError"
                            />
                            <div v-else class="flex h-40 w-full items-center justify-center rounded-t-lg bg-muted text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image-off">
                                    <path d="M8.5 10.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z"/>
                                    <path d="M14.5 14.5c.828 0 1.5-.672 1.5-1.5s-.672-1.5-1.5-1.5-1.5.672-1.5 1.5.672 1.5 1.5 1.5Z"/>
                                    <path d="M21 15l-5-5L7 21"/><path d="m2 2 20 20"/>
                                    <path d="M12.56 12.56c-.22-.13-.47-.21-.74-.26-.26-.05-.53-.08-.81-.08a4.5 4.5 0 0 0-4.48 4.17c.05.28.08.55.08.83A4.5 4.5 0 0 0 11 21.5c.28 0 .55-.03.83-.08a4.5 4.5 0 0 0 4.17-4.48c0-.28-.03-.55-.08-.83a4.5 4.5 0 0 0-3.37-3.61Z"/>
                                </svg>
                            </div>
                        </CardHeader>
                        <CardContent class="p-4">
                            <CardTitle class="mb-1 truncate text-lg">{{ place.name }}</CardTitle>
                             <Badge variant="secondary" class="mb-2">
                                <img v-if="place.category_icon" :src="place.category_icon" :alt="place.category" class="mr-1 h-4 w-4" />
                                {{ place.category || place.categories?.[0]?.name || 'N/A' }}
                            </Badge>
                            <p v-if="place.address || place.location?.formatted_address" class="truncate text-sm text-muted-foreground">
                                {{ place.address || place.location?.formatted_address }}
                            </p>
                            <p v-else class="text-sm italic text-muted-foreground">Address not available</p>
                        </CardContent>
                    </Card>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="absolute right-2 top-2 z-10 h-8 w-8 rounded-full bg-black/30 text-white hover:bg-black/50"
                        @click.stop="$emit('toggleFavorite', place)"
                        :title="isFavorite(place) ? 'Remove from Favorites' : 'Add to Favorites'"
                    >
                        <Star :fill="isFavorite(place) ? 'currentColor' : 'none'" class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <div v-else-if="coordinates" class="py-6 text-center text-muted-foreground">
                No nearby places found for this location.
            </div>
             <!-- Optional: Add a message if coordinates are not yet available -->
            <div v-else class="py-6 text-center text-muted-foreground">
                Click the map or search to find places.
            </div>
        </CardContent>
    </Card>
</template>
