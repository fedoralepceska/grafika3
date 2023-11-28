<template>
<nav class="relative flex justify-center">
    <template  v-for="link in pagination.links" :key="link.label">
    <Link
        preserve-scroll
        :href="link.url ?? ''"
        v-html="link.label"
        class="flex items-center justify-center px-3 py-2 text-white rounded-lg text-sm"
        :class="{'bg-gray-800' : link.active, '!text-gray-500': !link.url, 'cursor-default': !link.url}"
    />
    </template>
</nav>
</template>
<script setup>
import {Link} from '@inertiajs/vue3'
defineProps({
    pagination: Object
})
</script>
