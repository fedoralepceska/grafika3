<template>
<nav class="relative flex justify-center">
    <template v-for="link in pagination.links" :key="link.label">
        <button
            @click="handlePageClick(link)"
            v-html="link.label"
            :disabled="!link.url"
            class="flex items-center justify-center px-3 py-2 text-white rounded-lg text-sm"
            :class="{'bg-gray-800' : link.active, '!text-gray-500': !link.url, 'cursor-default': !link.url}"
        />
    </template>
</nav>
</template>
<script setup>
const props = defineProps({
    pagination: Object
})

const emit = defineEmits(['pagination-change-page'])

const handlePageClick = (link) => {
    if (!link.url) return
    
    // Extract page number from URL
    const url = new URL(link.url)
    const page = url.searchParams.get('page') || 1
    
    emit('pagination-change-page', parseInt(page))
}
</script>
