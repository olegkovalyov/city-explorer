<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps({
    localTime: {
        type: String,
        default: null,
    },
    localCurrency: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <Card class="mt-4">
        <CardHeader>
            <CardTitle class="text-md">Local Info</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
            <p v-if="localTime"><span class="font-medium">Time:</span> {{ localTime }}</p>
            <p v-else class="text-muted-foreground">Local time not available.</p>
            <p v-if="localCurrency"><span class="font-medium">Currency:</span> {{ localCurrency }}</p>
             <p v-else class="text-muted-foreground">Local currency not available.</p>
        </CardContent>
    </Card>
</template>
