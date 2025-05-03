<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button'; // Import Button
import ApplicationLogo from '@/components/ApplicationLogo.vue';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
    },
    phpVersion: {
        type: String,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Welcome to City Explorer" />
    <div class="relative min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 text-white">

        <!-- Main Content -->
        <main class="text-center z-10 p-8">
            <h1 class="text-5xl md:text-7xl font-bold mb-4 animate-fade-in-down">
                City Explorer
            </h1>
            <p class="text-xl md:text-2xl text-indigo-100 mb-8 animate-fade-in-up">
                Your ultimate guide to discovering cities around the world.
            </p>
            <p class="max-w-2xl mx-auto text-lg text-indigo-200 mb-10 animate-fade-in">
                Get real-time weather updates, explore interactive maps, and browse stunning photos.
                Log in or register to save your favorite destinations!
            </p>

            <!-- Call to Action Buttons -->
            <div class="space-x-4 animate-bounce-slow">
                <Button v-if="$page.props.auth.user" as="a" :href="route('city-explorer')" variant="primary" class="py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition duration-300 ease-in-out transform hover:-translate-y-1">
                    Start Exploring
                </Button>
                <template v-else>
                    <Button v-if="canRegister" as="a" :href="route('register')" variant="secondary" class="py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition duration-300 ease-in-out transform hover:-translate-y-1">
                        Get Started
                    </Button>
                    <Button as="a" :href="route('login')" variant="ghost" class="py-3 px-8 rounded-full hover:bg-white hover:text-indigo-700 transition duration-300 ease-in-out transform hover:-translate-y-1">
                        Log In
                    </Button>
                </template>
            </div>
        </main>

        <!-- Footer (Optional) -->
        <footer class="absolute bottom-0 p-6 text-center w-full text-sm text-indigo-200/80 z-10">
            Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
        </footer>

         <!-- Subtle Background Elements (Optional) -->
        <div class="absolute inset-0 overflow-hidden z-0">
            <div class="absolute top-1/4 left-10 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
            <div class="absolute top-1/2 right-10 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/3 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
        </div>

    </div>
</template>

<style>
/* Simple Tailwind CSS Animations */
@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down {
  animation: fadeInDown 0.8s ease-out forwards;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fadeInUp 0.8s ease-out 0.2s forwards; /* Delay */
  opacity: 0; /* Start hidden */
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.animate-fade-in {
  animation: fadeIn 1s ease-in 0.4s forwards; /* Delay */
  opacity: 0; /* Start hidden */
}

@keyframes bounceSlow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
.animate-bounce-slow {
  animation: bounceSlow 2s ease-in-out infinite 0.8s; /* Delay */
}

/* Blob animation */
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
.animation-delay-4000 {
  animation-delay: 4s;
}

</style>
