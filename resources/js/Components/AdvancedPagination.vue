<template>
<nav class="relative flex justify-center">
    <template v-for="link in pagination.links" :key="link.label">
        <button
            @click="navigateToPage(link)"
            v-html="link.label"
            class="flex items-center justify-center px-3 py-2 text-white rounded-lg text-sm"
            :class="{
                'bg-gray-800': link.active,
                '!text-gray-500': !link.url,
                'cursor-default': !link.url,
                'cursor-pointer': link.url
            }"
            :disabled="!link.url"
        />
    </template>
</nav>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    pagination: Object,
    preserveParams: {
        type: Object,
        default: () => ({})
    }
})

const navigateToPage = (link) => {
    if (!link.url) return;

    const url = new URL(link.url);
    
    // Add all preserved parameters
    Object.entries(props.preserveParams).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value);
        }
    });

    router.get(url.toString(), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}
</script> 