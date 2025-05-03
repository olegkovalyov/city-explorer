<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

// Refs for search functionality
const cityName = ref('');
const coordinates = ref(null);
const isLoading = ref(false);
const error = ref(null);

// Function to search for city coordinates
const searchCity = async () => {
    if (!cityName.value.trim()) {
        error.value = 'Please enter a city name.';
        coordinates.value = null;
        return;
    }

    isLoading.value = true;
    error.value = null;
    coordinates.value = null;

    try {
        // Make API call to our Laravel backend
        const response = await axios.get('/api/geocode', {
            params: {
                city: cityName.value
            }
        });
        coordinates.value = response.data;
    } catch (err) {
        console.error('Error fetching coordinates:', err);
        if (err.response && err.response.status === 404) {
            error.value = `Could not find coordinates for "${cityName.value}".`;
        } else if (err.response && err.response.data && err.response.data.message) {
             error.value = err.response.data.message;
        } else if (err.response && err.response.data && err.response.data.errors) {
            // Handle validation errors if needed
            error.value = Object.values(err.response.data.errors).flat().join(' ');
        } else {
            error.value = 'An error occurred while fetching coordinates.';
        }
        coordinates.value = null;
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>
    <Head title="City Explorer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">City Explorer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <CardHeader>
                        <CardTitle class="text-lg font-medium text-gray-900 dark:text-gray-100">City Explorer</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="searchCity" class="space-y-4">
                            <div class="flex space-x-2">
                                <Input
                                    id="city"
                                    type="text"
                                    v-model="cityName"
                                    placeholder="Enter city name"
                                    class="mt-1 block w-full"
                                    :disabled="isLoading"
                                    required
                                />
                                <Button type="submit" :disabled="isLoading">
                                    <span v-if="isLoading">Searching...</span>
                                    <span v-else>Search</span>
                                </Button>
                            </div>
                        </form>

                        <div v-if="isLoading" class="mt-4 text-center text-gray-600 dark:text-gray-400">
                            Loading...
                        </div>

                        <div v-if="error" class="mt-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded">
                            Error: {{ error }}
                        </div>

                        <div v-if="coordinates" class="mt-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-800 dark:text-green-200 rounded">
                            <h3 class="font-semibold">Coordinates for {{ cityName }}:</h3>
                            <p>Latitude: {{ coordinates.latitude }}</p>
                            <p>Longitude: {{ coordinates.longitude }}</p>
                        </div>

                        <!-- Placeholder for Weather -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium">Weather</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Weather data will appear here.</p>
                            <!-- Weather Component will go here -->
                        </div>

                        <!-- Placeholder for Map -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium">Map</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Map will appear here.</p>
                            <!-- Map Component will go here -->
                        </div>

                        <!-- Placeholder for Photos -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium">Photos</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Photos will appear here.</p>
                            <!-- Photos Component will go here -->
                        </div>

                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
