<template>
    <div class="relative">
        <input
            type="text"
            v-model="searchQuery"
            @input="handleSearch"
            @focus="showDropdown = true"
            class="w-full rounded option"
            placeholder="Search articles by name or code..."
        />

        <div v-if="showDropdown && filteredArticles.length > 0"
             class="absolute z-50 w-full mt-1 bg-gray-800 rounded shadow-lg max-h-60 overflow-y-auto">
            <div v-for="article in filteredArticles"
                 :key="article.id"
                 @click="selectArticle(article)"
                 class="p-2 hover:bg-gray-700 cursor-pointer">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-white font-medium">{{ article.name }}</div>
                        <div class="text-sm text-gray-400">
                            <span>Code: {{ article.code }}</span>
                            <span class="ml-2">({{ getUnitLabel(article) }})</span>
                        </div>
                    </div>
                    <div class="text-sm text-green-400">
                        €{{ article.purchase_price }}
                    </div>
                </div>
            </div>
        </div>

        <!-- No Results Message -->
        <div v-else-if="showDropdown && searchQuery.length >= 2"
             class="absolute z-50 w-full mt-1 bg-gray-800 rounded shadow-lg p-4">
            <div class="text-gray-400 text-center">No articles found</div>
        </div>
    </div>
</template>

<script>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import debounce from 'lodash.debounce';
import axios from 'axios';

export default {
    name: 'CatalogArticleSelect',

    props: {
        modelValue: {
            type: [Number, String],
            default: null
        }
    },

    emits: ['update:modelValue', 'article-selected'],

    setup(props, { emit }) {
        const searchQuery = ref('');
        const filteredArticles = ref([]);
        const showDropdown = ref(false);
        const selectedArticle = ref(null);

        const getUnitLabel = (article) => {
            if (!article) return '';
            if (article.in_meters) return 'm';
            if (article.in_kilograms) return 'kg';
            if (article.in_pieces) return 'pcs';
            if (article.in_square_meters) return 'm²';
            return '';
        };

        const getUnitType = (article) => {
            if (!article) return '';
            if (article.in_meters) return 'meters';
            if (article.in_kilograms) return 'kilograms';
            if (article.in_pieces) return 'pieces';
            if (article.in_square_meters) return 'square_meters';
            return '';
        };

        // Debounced search function
        const handleSearch = debounce(async () => {
            console.log('Searching for:', searchQuery.value);

            if (searchQuery.value.length < 2) {
                filteredArticles.value = [];
                return;
            }

            try {
                const response = await axios.get('/articles/search', {
                    params: { query: searchQuery.value }
                });
                console.log('Search response:', response.data);
                filteredArticles.value = response.data;
                showDropdown.value = true;
            } catch (error) {
                console.error('Error searching articles:', error.response || error);
                filteredArticles.value = [];
            }
        }, 300);

        const selectArticle = (article) => {
            console.log('Selected article:', article);
            selectedArticle.value = article;
            searchQuery.value = article.name;
            showDropdown.value = false;
            emit('update:modelValue', article.id);
            emit('article-selected', {
                ...article,
                unitType: getUnitType(article),
                unitLabel: getUnitLabel(article)
            });
        };

        // Watch for changes in modelValue prop
        watch(() => props.modelValue, async (newValue) => {
            console.log('Model value changed:', newValue);
            if (newValue && !selectedArticle.value) {
                try {
                    const response = await axios.get(`/articles/${newValue}`);
                    console.log('Fetched article:', response.data);
                    const article = response.data;
                    selectedArticle.value = article;
                    searchQuery.value = article.name;
                } catch (error) {
                    console.error('Error fetching article:', error.response || error);
                }
            } else if (!newValue) {
                selectedArticle.value = null;
                searchQuery.value = '';
            }
        }, { immediate: true });

        // Close dropdown when clicking outside
        const handleClickOutside = (event) => {
            if (!event.target.closest('.relative')) {
                showDropdown.value = false;
            }
        };

        onMounted(() => {
            document.addEventListener('click', handleClickOutside);
            // Initialize with existing article if modelValue is present
            if (props.modelValue) {
                axios.get(`/articles/${props.modelValue}`).then(response => {
                    const article = response.data;
                    selectedArticle.value = article;
                    searchQuery.value = article.name;
                }).catch(error => {
                    console.error('Error fetching initial article:', error);
                });
            }
        });

        onUnmounted(() => {
            document.removeEventListener('click', handleClickOutside);
        });

        return {
            searchQuery,
            filteredArticles,
            showDropdown,
            selectedArticle,
            handleSearch,
            selectArticle,
            getUnitLabel
        };
    }
}
</script>

<style scoped>
.option {
    @apply bg-gray-700 border-gray-600 text-white;
}

.relative {
    position: relative;
}

/* Dropdown styles */
.absolute {
    position: absolute;
    width: 100%;
    z-index: 50;
}

/* Input styles */
input.option {
    width: 100%;
    padding: 0.5rem;
    border-radius: 0.25rem;
    outline: none;
}

input.option:focus {
    border-color: #4f46e5;
}

/* Dropdown item styles */
.cursor-pointer {
    cursor: pointer;
}

.hover\:bg-gray-700:hover {
    background-color: #374151;
}
</style>
