<!-- resources/js/Components/GalleryModal.vue -->
<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogClose,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    place: {
        type: Object,
        default: null, // Default to null when no place is selected
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:isOpen']);

const handleUpdateOpen = (value) => {
    emit('update:isOpen', value);
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleUpdateOpen">
        <DialogContent v-if="place" class="sm:max-w-[80%]">
            <DialogHeader>
                <DialogTitle>{{ place.name }}</DialogTitle>
                <DialogDescription>
                    {{ place.address || place.location?.formatted_address || 'Address not available' }}
                </DialogDescription>
            </DialogHeader>
             <div class="relative flex max-h-[70vh] min-h-[40vh] flex-1 items-center justify-center overflow-hidden bg-gray-100 p-2 dark:bg-gray-800">
                <!-- Scrollable container for the grid -->
                <div class="h-full w-full overflow-y-auto p-4">
                    <!-- Loading State -->
                    <div v-if="loading" class="flex h-full items-center justify-center">
                        <svg
                            class="-ml-1 mr-3 h-10 w-10 animate-spin text-primary"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span class="text-muted-foreground">Loading details...</span>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="p-4 text-center text-destructive">
                        <p><strong>Error:</strong> {{ error }}</p>
                    </div>

                    <!-- Image Grid Display State -->
                    <div
                        v-else-if="place.photos && place.photos.length > 0"
                         class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                    >
                        <div
                            v-for="(photoUrl, index) in place.photos"
                            :key="photoUrl + '-' + index"
                            class="aspect-square overflow-hidden rounded-md bg-muted"
                        >
                            <img
                                :src="photoUrl"
                                :alt="`Photo ${index + 1} for ${place.name}`"
                                class="h-full w-full cursor-pointer object-cover transition-transform duration-300 hover:scale-105"
                                loading="lazy"
                            />
                        </div>
                    </div>

                    <!-- No Photos State -->
                     <div v-else class="flex h-full items-center justify-center text-center text-muted-foreground">
                        {{
                            error && error.includes('No photos found')
                                ? error
                                : 'No photos available for this place.'
                        }}
                    </div>
                </div>
            </div>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">Close</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
         <!-- Optional: Render a minimal DialogContent if place is null but isOpen is true,
             though ideally the parent shouldn't open the dialog without a place -->
        <DialogContent v-else class="sm:max-w-[425px]">
             <DialogHeader>
                <DialogTitle>No Place Selected</DialogTitle>
            </DialogHeader>
            <div class="py-4">Please select a place to view the gallery.</div>
             <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="secondary">Close</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
