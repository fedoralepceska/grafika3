<template>
    <nav v-if="pagination" class="relative flex justify-center">
        <!-- Previous Page -->
        <button 
            @click="$emit('page-changed', pagination.current_page - 1)"
            :disabled="!pagination.prev_page_url"
            class="flex items-center justify-center px-3 py-2 text-white rounded-lg text-sm"
            :class="{'!text-gray-500': !pagination.prev_page_url, 'cursor-default': !pagination.prev_page_url}"
        >
            Previous
        </button>

        <!-- Page Numbers -->
        <template v-for="page in getPageNumbers" :key="page">
            <button 
                @click="$emit('page-changed', page)"
                class="flex items-center justify-center px-3 py-2 text-white rounded-lg text-sm"
                :class="{
                    'bg-gray-800': page === pagination.current_page,
                    '!text-gray-500': page === '...',
                    'cursor-default': page === '...'
                }"
                :disabled="page === '...'"
            >
                {{ page }}
            </button>
        </template>

        <!-- Next Page -->
        <button 
            @click="$emit('page-changed', pagination.current_page + 1)"
            :disabled="!pagination.next_page_url"
            class="flex items-center justify-center px-3 py-2 text-white rounded-lg text-sm"
            :class="{'!text-gray-500': !pagination.next_page_url, 'cursor-default': !pagination.next_page_url}"
        >
            Next
        </button>
    </nav>
</template>

<script>
export default {
    props: {
        pagination: {
            type: Object,
            required: true
        }
    },
    computed: {
        getPageNumbers() {
            if (!this.pagination) return [];
            
            let pages = [];
            const currentPage = this.pagination.current_page;
            const lastPage = this.pagination.last_page;

            // Always show first page
            if (currentPage > 3) pages.push(1);
            if (currentPage > 4) pages.push('...');

            // Show pages around current page
            for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
                pages.push(i);
            }

            // Always show last page
            if (currentPage < lastPage - 3) pages.push('...');
            if (currentPage < lastPage - 2) pages.push(lastPage);

            return pages;
        }
    }
}
</script>