<template>
    <div class="relative">
        <button
            type="button"
            @click="toggleDropdown"
            class="w-full min-w-[200px] text-black filter-select text-left flex items-center justify-between"
            :class="{ 'bg-gray-300': showDropdown }"
        >
            <span class="truncate">
                {{ selectedClient ? selectedClient.name : 'All Clients' }}
            </span>
            <i class="fas fa-chevron-down ml-2 text-xs transition-transform flex-shrink-0" 
               :class="{ 'transform rotate-180': showDropdown }"></i>
        </button>

        <!-- Search Input -->
        <div v-if="showDropdown" class="absolute z-50 w-full min-w-[300px] max-w-[500px] mt-1 bg-gray-800 rounded-md shadow-lg border border-gray-600">
            <div class="p-3 border-b border-gray-700">
                <input
                    v-model="searchQuery"
                    @input="handleSearch"
                    type="text"
                    class="w-full bg-gray-700 text-white rounded px-3 py-2 text-sm border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Search clients..."
                    ref="searchInput"
                />
            </div>
            
            <!-- Dropdown Options -->
            <div class="max-h-64 overflow-y-auto">
                <!-- All Clients Option -->
                <div 
                    @click="selectOption(null)"
                    class="px-3 py-2 hover:bg-gray-700 cursor-pointer flex items-center justify-between"
                    :class="{ 'bg-green-600': !modelValue }"
                >
                    <span class="text-white text-sm">All Clients</span>
                    <i v-if="!modelValue" class="fas fa-check text-green-400 text-xs"></i>
                </div>

                <!-- Client Options -->
                <div 
                    v-for="client in filteredClients"
                    :key="client.id"
                    @click="selectOption(client)"
                    class="px-3 py-2 hover:bg-gray-700 cursor-pointer flex items-center justify-between"
                    :class="{ 'bg-green-600': modelValue === client.name }"
                >
                    <div class="text-white text-sm break-words pr-2">{{ client.name }}</div>
                    <i v-if="modelValue === client.name" class="fas fa-check text-green-400 text-xs flex-shrink-0"></i>
                </div>

                <!-- No Results -->
                <div v-if="filteredClients.length === 0 && searchQuery.length > 0" 
                     class="px-3 py-3 text-sm text-gray-400 text-center">
                    No clients found
                </div>
            </div>
        </div>

        <!-- Click Outside Overlay -->
        <div v-if="showDropdown" class="fixed inset-0 z-40" @click="closeDropdown"></div>
    </div>
</template>

<script>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import debounce from 'lodash.debounce';

export default {
    name: 'ClientSelectDropdown',
    
    props: {
        modelValue: {
            type: String,
            default: 'All'
        },
        clients: {
            type: Array,
            default: () => []
        }
    },

    emits: ['update:modelValue', 'change'],

    setup(props, { emit }) {
        const showDropdown = ref(false);
        const searchQuery = ref('');
        const searchInput = ref(null);

        // Computed
        const filteredClients = computed(() => {
            if (!searchQuery.value) return props.clients;
            
            const query = searchQuery.value.toLowerCase();
            return props.clients.filter(client => 
                client.name.toLowerCase().includes(query)
            );
        });

        const selectedClient = computed(() => {
            if (props.modelValue === 'All') return null;
            return props.clients.find(client => client.name === props.modelValue);
        });

        // Methods
        const toggleDropdown = async () => {
            showDropdown.value = !showDropdown.value;
            if (showDropdown.value) {
                await nextTick();
                searchInput.value?.focus();
            }
        };

        const closeDropdown = () => {
            showDropdown.value = false;
            searchQuery.value = '';
        };

        const selectOption = (client) => {
            const value = client ? client.name : 'All';
            emit('update:modelValue', value);
            emit('change', value);
            closeDropdown();
        };


        // Debounced search (not needed for local filtering, but keeping for consistency)
        const handleSearch = debounce(() => {
            // Search is handled reactively via computed property
        }, 100);

        // Close on Escape key
        const handleKeydown = (e) => {
            if (e.key === 'Escape' && showDropdown.value) {
                closeDropdown();
            }
        };

        // Event listeners
        onMounted(() => {
            document.addEventListener('keydown', handleKeydown);
        });

        onUnmounted(() => {
            document.removeEventListener('keydown', handleKeydown);
        });

        return {
            showDropdown,
            searchQuery,
            searchInput,
            filteredClients,
            selectedClient,
            toggleDropdown,
            closeDropdown,
            selectOption,
            handleSearch
        };
    }
};
</script>

<style scoped>
.filter-select {
    padding: 0.5rem 0.75rem;
    border-radius: 0.25rem;
    background-color: white;
    border: 1px solid #d1d5db;
    min-height: 2.5rem;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.transition-transform {
    transition-property: transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>
