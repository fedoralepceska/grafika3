<template>
  <div v-if="shouldAskQuestions" class="questions-selector p-4 border-dashed border-2 border-gray-500">
    <h4 class="text-white text-md font-medium mb-4">Select Questions for This Item</h4>
    <div v-if="loading" class="text-white">Loading questions...</div>
    <div v-else-if="availableQuestions.length === 0" class="text-gray-400">No questions available</div>
    <div v-else class="space-y-2">
      <div v-for="question in availableQuestions" :key="question.id" class="flex items-center">
        <input
          type="checkbox"
          :id="`question-${question.id}`"
          :value="question.id"
          v-model="selectedQuestions"
          class="mr-3 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
        />
        <label :for="`question-${question.id}`" class="text-white text-sm">
          {{ question.question }}
        </label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
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

const fetchAvailableQuestions = async () => {
  if (!props.shouldAskQuestions) return;
  
  loading.value = true;
  try {
    const response = await axios.get('/questions/active');
    availableQuestions.value = response.data;
  } catch (error) {
    console.error('Error fetching questions:', error);
  } finally {
    loading.value = false;
  }
};

const fetchSelectedQuestions = async () => {
  if (!props.catalogItemId || !props.shouldAskQuestions) return;
  
  try {
    const response = await axios.get(`/questions/catalog-item/${props.catalogItemId}`);
    selectedQuestions.value = response.data.map(q => q.id);
  } catch (error) {
    console.error('Error fetching selected questions:', error);
  }
};

// Watch for changes in selected questions and emit to parent
watch(selectedQuestions, (newValue) => {
  emit('update:modelValue', newValue);
}, { deep: true });

// Watch for changes in shouldAskQuestions prop
watch(() => props.shouldAskQuestions, (newValue) => {
  if (newValue) {
    fetchAvailableQuestions();
    if (props.catalogItemId) {
      fetchSelectedQuestions();
    }
  } else {
    selectedQuestions.value = [];
  }
});

// Watch for changes in modelValue prop
watch(() => props.modelValue, (newValue) => {
  selectedQuestions.value = [...newValue];
}, { immediate: true });

onMounted(() => {
  if (props.shouldAskQuestions) {
    fetchAvailableQuestions();
    if (props.catalogItemId) {
      fetchSelectedQuestions();
    }
  }
});
</script>

<style scoped lang="scss">
.questions-selector {
  background-color: rgba(55, 65, 81, 0.1);
}
</style> 