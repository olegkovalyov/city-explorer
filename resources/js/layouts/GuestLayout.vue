<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 p-4"
    >
        <!-- Using standard bg-white instead of bg-card -->
        <Card class="w-full max-w-md shadow-xl bg-white text-card-foreground">
            <CardHeader class="items-center text-center">
                <Link href="/" class="mb-4 inline-block">
                    <ApplicationLogo class="h-16 w-auto text-primary" />
                </Link>
                <!-- Optional: Add a CardTitle if needed -->
                <!-- <CardTitle>Welcome</CardTitle> -->
            </CardHeader>
            <CardContent>
                <slot />
            </CardContent>
        </Card>
    </div>
</template>
