<template>
  <div v-show="shouldAskQuestions" class="questions-selector p-4 border-dashed border-2 border-gray-500 dark-gray">
    <div class="flex items-center justify-between mb-4">
      <h4 class="text-white text-md font-medium">Select Questions for This Item</h4>
      <div v-if="loading" class="text-green-400 text-sm flex items-center">
        <i class="fas fa-spinner fa-spin mr-1"></i>
        Loading...
      </div>
    </div>
    

    
    <div v-if="!loading && availableQuestions.length === 0" class="text-gray-400">
      No questions available
    </div>
    
    <div v-else class="space-y-2">
      <div v-for="question in availableQuestions" :key="question.id" class="flex items-center">
        <input
          type="checkbox"
          :id="`question-${question.id}`"
          :value="question.id"
          v-model="selectedQuestions"
          class="mr-3 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
        />
        <label :for="`question-${question.id}`" class="text-white text-sm cursor-pointer">
          {{ question.question }}
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  shouldAskQuestions: {
    type: Boolean,
    default: false
  },
  catalogItemId: {
    type: [Number, String],
    default: null
  },
  modelValue: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue']);

const availableQuestions = ref([]);
const selectedQuestions = ref([]);
const loading = ref(false);

// Cache to avoid re-fetching
const questionsCache = {
  available: null,
  selected: new Map()
};

const fetchAvailableQuestions = async () => {
  // Return cached data if available
  if (questionsCache.available) {
    availableQuestions.value = questionsCache.available;
    return;
  }
  
  loading.value = true;
  try {
    const response = await axios.get('/questions/active');
    questionsCache.available = response.data;
    availableQuestions.value = response.data;
  } catch (error) {
    console.error('Error fetching questions:', error);
  } finally {
    loading.value = false;
  }
};

const fetchSelectedQuestions = async () => {
  if (!props.catalogItemId) return;
  
  // Return cached data if available
  const cacheKey = props.catalogItemId.toString();
  if (questionsCache.selected.has(cacheKey)) {
    selectedQuestions.value = questionsCache.selected.get(cacheKey);
    return;
  }
  
  try {
    const response = await axios.get(`/questions/catalog-item/${props.catalogItemId}`);
    const selected = response.data.map(q => q.id);
    questionsCache.selected.set(cacheKey, selected);
    selectedQuestions.value = selected;
  } catch (error) {
    console.error('Error fetching selected questions:', error);
  }
};

// Optimized: Single watcher for selected questions
watch(selectedQuestions, (newValue) => {
  emit('update:modelValue', newValue);
}, { deep: true });

// Optimized: Single watcher for shouldAskQuestions with async loading and debouncing
let loadTimeout = null;
watch(() => props.shouldAskQuestions, async (newValue) => {
  // Clear any pending loads
  if (loadTimeout) {
    clearTimeout(loadTimeout);
  }
  
  if (newValue) {
    // Debounce the loading to prevent rapid toggling issues
    loadTimeout = setTimeout(() => {
      nextTick(() => {
        fetchAvailableQuestions();
        // Don't fetch selected questions here - let parent handle via v-model
      });
    }, 100);
  } else {
    selectedQuestions.value = [];
  }
});

// Initialize from prop
watch(() => props.modelValue, (newValue) => {
  selectedQuestions.value = [...newValue];
}, { immediate: true });

// Pre-load questions when component is created (for better UX)
if (questionsCache.available === null) {
  fetchAvailableQuestions();
}

// Cleanup timeouts on unmount
onUnmounted(() => {
  if (loadTimeout) {
    clearTimeout(loadTimeout);
  }
});
</script>

<style scoped lang="scss">
.questions-selector {
  background-color: rgba(55, 65, 81, 0.1);
  transition: all 0.3s ease;
}

.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 