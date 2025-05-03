<script setup>
import { Checkbox } from '@/components/ui/checkbox';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />
        <!-- Removed test heading -->

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center">
                      <Label for="password">Password</Label>
                      <Link
                          v-if="canResetPassword"
                          :href="route('password.request')"
                          class="ml-auto inline-block text-sm underline"
                      >
                          Forgot your password?
                      </Link>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox id="remember" name="remember" v-model:checked="form.remember" />
                    <label
                      for="remember"
                      class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Remember me
                    </label>
                </div>

                <Button :disabled="form.processing" type="submit" class="w-full" variant="default">
                    Log in
                </Button>

                 <!-- Optional: Add a link to Register page if needed -->
                 <div class="mt-4 text-center text-sm">
                   Don't have an account?
                   <Link :href="route('register')" class="underline">
                     Sign up
                   </Link>
                 </div>
            </div>
        </form>
    </GuestLayout>
</template>
