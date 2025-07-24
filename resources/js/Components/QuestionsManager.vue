<template>
  <div>
    <h2>Questions Manager</h2>
    <button @click="openAddModal">Add Question</button>
    <draggable v-model="questions" @end="onReorder" item-key="id">
      <template #item="{element, index}">
        <div class="question-row" :class="{'inactive': !element.active}">
          <span>{{ index + 1 }}.</span>
          <span>{{ element.question }}</span>
          <button @click="openEditModal(element)">Edit</button>
          <button v-if="element.active" @click="disableQuestion(element)">Disable</button>
          <button v-else @click="enableQuestion(element)">Enable</button>
          <button @click="deleteQuestion(element)">Delete</button>
        </div>
      </template>
    </draggable>

    <!-- Add/Edit Modal -->
    <Modal v-if="showModal" @close="closeModal">
      <template #header>
        <h3>{{ editMode ? 'Edit Question' : 'Add Question' }}</h3>
      </template>
      <template #body>
        <form @submit.prevent="saveQuestion">
          <div>
            <label>Question:</label>
            <input v-model="modalData.question" required />
          </div>
        </form>
      </template>
      <template #footer>
        <button @click="saveQuestion">Save</button>
        <button @click="closeModal">Cancel</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import draggable from 'vuedraggable';
import Modal from './Modal.vue';
import axios from 'axios';

const questions = ref([]);
const showModal = ref(false);
const editMode = ref(false);
const modalData = ref({ id: null, question: '' });

const fetchQuestions = async () => {
  const res = await axios.get('/questions');
  questions.value = res.data;
};

const openAddModal = () => {
  editMode.value = false;
  modalData.value = { id: null, question: '' };
  showModal.value = true;
};

const openEditModal = (q) => {
  editMode.value = true;
  modalData.value = { ...q };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveQuestion = async () => {
  if (editMode.value) {
    await axios.put(`/questions/${modalData.value.id}`, {
      question: modalData.value.question,
    });
  } else {
    await axios.post('/questions', {
      question: modalData.value.question,
      order: questions.value.length,
      active: true,
    });
  }
  showModal.value = false;
  await fetchQuestions();
};

const enableQuestion = async (q) => {
  await axios.post(`/questions/${q.id}/enable`);
  await fetchQuestions();
};

const disableQuestion = async (q) => {
  await axios.post(`/questions/${q.id}/disable`);
  await fetchQuestions();
};

const deleteQuestion = async (q) => {
  if (confirm('Are you sure you want to delete this question?')) {
    await axios.delete(`/questions/${q.id}`);
    await fetchQuestions();
  }
};

const onReorder = async () => {
  const order = questions.value.map(q => q.id);
  await axios.post('/questions/reorder', { order });
  await fetchQuestions();
};

onMounted(fetchQuestions);
</script>

<style scoped>
.question-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.5rem 0;
}
.question-row.inactive {
  opacity: 0.5;
}
.default-answer {
  color: #888;
  font-style: italic;
}
</style> 