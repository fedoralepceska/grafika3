<template>
    <div class="article-dropdown-container" ref="container">
        <input
            type="text"
            :value="displayValue"
            @input="handleInput"
            @focus="handleFocus"
            @blur="handleBlur"
            @keyup.enter="handleEnter"
            :placeholder="placeholder"
            class="table-input"
            ref="input"
        />
        <Teleport to="body">
            <div v-if="showDropdown && filteredArticles.length > 0"
                 class="dropdown-menu"
                 ref="dropdown"
                 :style="dropdownStyle">
                <ul>
                    <li v-for="article in filteredArticles"
                        :key="article.id"
                        @mousedown.prevent="selectArticle(article)"
                        class="dropdown-item">
                        <div class="item-content" :class="{ 'reverse-order': searchType === 'name' }">
                            <div :class="{ 'primary': searchType === 'code', 'secondary': searchType === 'name' }">
                                {{ article.code }}
                            </div>
                            <div :class="{ 'primary': searchType === 'name', 'secondary': searchType === 'code' }">
                                {{ article.name }}
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue'
import axios from 'axios'
import debounce from 'lodash.debounce'

export default {
    name: 'ArticleSearchDropdown',
    props: {
        modelValue: {
            type: String,
            required: true
        },
        placeholder: {
            type: String,
            default: ''
        },
        searchType: {
            type: String,
            required: true,
            validator: value => ['code', 'name'].includes(value)
        }
    },
    emits: ['update:modelValue', 'article-selected'],
    setup(props, { emit }) {
        const showDropdown = ref(false)
        const articles = ref([])
        const filteredArticles = ref([])
        const input = ref(null)
        const container = ref(null)
        const dropdown = ref(null)
        const dropdownStyle = ref({})
        const selectedArticle = ref(null)

        // Computed property to display either code or name
        const displayValue = computed(() => {
            if (selectedArticle.value) {
                return selectedArticle.value[props.searchType]
            }
            return props.modelValue
        })

        const handleInput = (event) => {
            selectedArticle.value = null
            emit('update:modelValue', event.target.value)
        }

        const updateDropdownPosition = () => {
            if (!showDropdown.value || !container.value) return

            const containerRect = container.value.getBoundingClientRect()
            const windowHeight = window.innerHeight
            const dropdownHeight = 250 // Max height of dropdown

            // Calculate if dropdown should open upward or downward
            const spaceBelow = windowHeight - containerRect.bottom
            const openUpward = spaceBelow < dropdownHeight && containerRect.top > dropdownHeight

            // Position relative to the container
            dropdownStyle.value = {
                position: 'fixed',
                width: `${containerRect.width}px`,
                left: `${containerRect.left}px`,
                [openUpward ? 'bottom' : 'top']: `${openUpward ? (windowHeight - containerRect.top) : containerRect.bottom}px`,
                maxHeight: `${Math.min(dropdownHeight, openUpward ? containerRect.top : spaceBelow)}px`
            }
        }

        // Debounced version for scroll and resize events
        const debouncedUpdatePosition = debounce(updateDropdownPosition, 100)

        // Event listeners
        onMounted(() => {
            const parents = []
            let parent = container.value?.parentElement
            while (parent) {
                if (getComputedStyle(parent).overflow !== 'visible') {
                    parents.push(parent)
                }
                parent = parent.parentElement
            }
            
            parents.forEach(p => p.addEventListener('scroll', debouncedUpdatePosition))
            window.addEventListener('scroll', debouncedUpdatePosition, true)
            window.addEventListener('resize', debouncedUpdatePosition)
        })

        onUnmounted(() => {
            const parents = []
            let parent = container.value?.parentElement
            while (parent) {
                if (getComputedStyle(parent).overflow !== 'visible') {
                    parents.push(parent)
                }
                parent = parent.parentElement
            }
            
            parents.forEach(p => p.removeEventListener('scroll', debouncedUpdatePosition))
            window.removeEventListener('scroll', debouncedUpdatePosition, true)
            window.removeEventListener('resize', debouncedUpdatePosition)
        })

        // Watch for dropdown visibility changes
        watch(showDropdown, (newValue) => {
            if (newValue) {
                // Use nextTick to ensure the dropdown is rendered
                nextTick(() => {
                    updateDropdownPosition()
                })
            }
        })

        const fetchArticles = async () => {
            try {
                const response = await axios.get('/articles', {
                    params: {
                        per_page: 20
                    }
                })
                articles.value = response.data.data
                filterArticles(props.modelValue)
            } catch (error) {
                console.error('Error fetching articles:', error)
            }
        }

        const searchArticles = debounce(async (query) => {
            if (!query) return
            try {
                const response = await axios.get('/articles', {
                    params: {
                        search: query,
                        per_page: 20
                    }
                })
                filteredArticles.value = response.data.data
            } catch (error) {
                console.error('Error searching articles:', error)
            }
        }, 300)

        const filterArticles = (query) => {
            if (!query) {
                filteredArticles.value = articles.value
                return
            }

            const searchField = props.searchType
            const lowercaseQuery = query.toLowerCase()

            filteredArticles.value = articles.value.filter(article => {
                const fieldValue = article[searchField]?.toString().toLowerCase() || ''
                return fieldValue.includes(lowercaseQuery)
            })
        }

        const handleFocus = () => {
            if (articles.value.length === 0) {
                fetchArticles()
            }
            showDropdown.value = true
            nextTick(() => {
                updateDropdownPosition()
            })
        }

        const handleBlur = () => {
            // Delay hiding dropdown to allow click events to fire
            setTimeout(() => {
                showDropdown.value = false
            }, 200)
        }

        const handleEnter = () => {
            if (filteredArticles.value.length > 0) {
                selectArticle(filteredArticles.value[0])
            }
        }

        const selectArticle = (article) => {
            selectedArticle.value = article
            emit('update:modelValue', article[props.searchType])
            emit('article-selected', article)
            showDropdown.value = false
        }

        // Watch for external modelValue changes
        watch(() => props.modelValue, (newValue) => {
            if (!newValue) {
                selectedArticle.value = null
            }
            if (newValue) {
                searchArticles(newValue)
            } else {
                filterArticles('')
            }
        })

        return {
            showDropdown,
            filteredArticles,
            handleFocus,
            handleBlur,
            handleEnter,
            selectArticle,
            input,
            container,
            dropdown,
            dropdownStyle,
            updateDropdownPosition,
            displayValue,
            handleInput
        }
    }
}
</script>

<style scoped lang="scss">
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$hover-color: #4a5a68;

.article-dropdown-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.table-input {
    width: 100%;
    height: 100%;
    background-color: transparent;
    border: none;
    color: $white;
    padding: 8px;
    font-size: 0.9rem;
    outline: none;
    transition: background-color 0.2s ease;

    &::placeholder {
        color: $ultra-light-gray;
        opacity: 0.7;
    }

    &:focus {
        background-color: $dark-gray;
    }
}

.dropdown-menu {
    background-color: $background-color;
    border: 1px solid $gray;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    overflow-y: auto;
    z-index: 9999;

    /* Scrollbar styling */
    &::-webkit-scrollbar {
        width: 8px;
    }

    &::-webkit-scrollbar-track {
        background: $dark-gray;
        border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
        background: $light-gray;
        border-radius: 4px;

        &:hover {
            background: $ultra-light-gray;
        }
    }

    ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
}

.dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid $dark-gray;
    transition: background-color 0.2s ease;

    &:last-child {
        border-bottom: none;
    }

    &:hover {
        background-color: $hover-color;
    }
}

.item-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: $white;
    font-size: 0.9rem;
    gap: 12px;

    &.reverse-order {
        flex-direction: row-reverse;
    }

    .primary {
        font-weight: 500;
        color: $white;
        flex: 1;
    }

    .secondary {
        color: $ultra-light-gray;
        flex: 1;
        font-size: 0.85rem;
    }
}
</style>
