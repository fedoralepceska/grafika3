<template>
  <MainLayout>
    <div class="pl-7 pr-7">
      <Header title="Questions Manager" subtitle="Manage Questions" icon="questions.png" link="admin/questions" />
      <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">
          <h2 class="sub-title">Before Production Questions</h2>
          <div class="flex justify-end mb-4">
            <button class="btn" @click="openAddModal">
              <span class="mdi mdi-plus"></span> Add Question
            </button>
          </div>
          <draggable v-model="questions" @end="onReorder" item-key="id" class="questions-list" handle=".drag-handle">
            <template #item="{ element: q, index: idx }">
              <div class="question-card" :class="{'inactive': !q.active}">
                <span class="drag-handle mdi mdi-drag-vertical"></span>
                <div class="order-badge">{{ idx + 1 }}</div>
                <div class="question-content">
                  <div class="question-main">
                    <div class="question-text">{{ q.question }}</div>
                    <div class="default-answer">{{ q.default_answer }}</div>
                  </div>
                  <div class="question-meta">
                    <span v-if="q.active" class="status-badge active">Active</span>
                    <span v-else class="status-badge disabled">Disabled</span>
                    <div class="actions">
                      <button class="icon-btn" @click="openEditModal(q)"><span class="mdi mdi-pencil"></span></button>
                      <button class="icon-btn" v-if="q.active" @click="disableQuestion(q)"><span class="mdi mdi-eye-off"></span></button>
                      <button class="icon-btn" v-else @click="enableQuestion(q)"><span class="mdi mdi-eye"></span></button>
                      <button class="icon-btn" @click="deleteQuestion(q)"><span class="mdi mdi-delete"></span></button>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </draggable>
        </div>
      </div>
    </div>
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="modal-card">
        <button class="modal-close" @click="closeModal">&times;</button>
        <h3 class="modal-title">{{ editMode ? 'Edit Question' : 'Add Question' }}</h3>
        <form @submit.prevent="saveQuestion">
          <div class="mb-4">
            <label class="modal-label">Question:</label>
            <input v-model="form.question" class="modal-input" required />
          </div>
          <div class="mb-4">
            <label class="modal-label">Default Answer:</label>
            <input v-model="form.default_answer" class="modal-input" required />
          </div>
          <div class="flex gap-2 justify-end mt-6">
            <button class="btn" type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { useToast } from 'vue-toastification';
import draggable from 'vuedraggable';
import axios from 'axios';

const questions = ref([]);
const form = ref({ question: '', default_answer: '' });
const editMode = ref(false);
const editId = ref(null);
const showModal = ref(false);
const toast = useToast();

const fetchQuestions = async () => {
  const res = await axios.get('/questions');
  questions.value = res.data;
};

const openAddModal = () => {
  editMode.value = false;
  editId.value = null;
  form.value = { question: '', default_answer: '' };
  showModal.value = true;
};

const openEditModal = (q) => {
  editMode.value = true;
  editId.value = q.id;
  form.value = { question: q.question, default_answer: q.default_answer };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = { question: '', default_answer: '' };
  editMode.value = false;
  editId.value = null;
};

const saveQuestion = async () => {
  try {
    if (editMode.value) {
      await axios.put(`/questions/${editId.value}`, {
        question: form.value.question,
        default_answer: form.value.default_answer,
      });
      toast.success('Question updated!');
    } else {
      await axios.post('/questions', {
        question: form.value.question,
        default_answer: form.value.default_answer,
        order: questions.value.length,
        active: true,
      });
      toast.success('Question added!');
    }
    closeModal();
    await fetchQuestions();
  } catch (e) {
    toast.error('Error saving question.');
  }
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

<style scoped lang="scss">
.dark-gray {
  background-color: $dark-gray;
}

.light-gray {
  background-color: $light-gray;
}

.sub-title {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  color: $white;
}

.btn {
  padding: 7px 10px;
  border: none;
  cursor: pointer;
  font-weight: bold;
  border-radius: 3px;
  background-color: $green;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.98rem;
}

.questions-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.question-card {
  display: flex;
  align-items: center;
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  padding: 0.7rem 1rem;
  transition: box-shadow 0.2s, background 0.2s;
  position: relative;
  gap: 1rem;
  border: 1px solid $dark-gray;
  min-height: 48px;
}
.question-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.10);
}
.question-card.inactive {
  opacity: 0.5;
}
.drag-handle {
  font-size: 1.3rem;
  color: #b0b0b0;
  margin-right: 0.7rem;
  cursor: grab;
  user-select: none;
  display: flex;
  align-items: center;
}
.order-badge {
  min-width: 28px;
  height: 28px;
  background: $green;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.98rem;
  margin-right: 0.7rem;
}
.question-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.question-main {
  font-size: 1rem;
  font-weight: 500;
  color: $white;
}
.question-text {
  margin-bottom: 0.1rem;
}
.default-answer {
  color: #b0b0b0;
  font-style: italic;
  font-size: 0.93rem;
}
.question-meta {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  margin-top: 0.2rem;
}
.status-badge {
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 0.93rem;
  font-weight: 600;
}
.status-badge.active {
  background: #2ecc40;
  color: #fff;
}
.status-badge.disabled {
  background: #c95050;
  color: #fff;
}
.actions {
  display: flex;
  gap: 0.3rem;
}
.icon-btn {
  background: none;
  border: none;
  color: #b0b0b0;
  font-size: 1.1rem;
  cursor: pointer;
  transition: color 0.15s;
  padding: 3px;
  border-radius: 4px;
}
.icon-btn:hover {
  color: $green;
  background: #23272f;
}

/* Modal styles matching system theme */
.modal-card {
  background: $dark-gray;
  color: $white;
  border-radius: 12px;
  box-shadow: 0 4px 32px rgba(0,0,0,0.25);
  padding: 2.5rem 2rem 2rem 2rem;
  min-width: 350px;
  max-width: 420px;
  width: 100%;
  position: relative;
  display: flex;
  flex-direction: column;
}
.modal-title {
  font-size: 1.3rem;
  font-weight: bold;
  margin-bottom: 1.5rem;
  color: $white;
}
.modal-label {
  color: $white;
  font-weight: 500;
  margin-bottom: 0.3rem;
  display: block;
}
.modal-input {
  width: 100%;
  background: $light-gray;
  color: $white;
  border: 1px solid #444;
  padding: 0.6rem 0.9rem;
  font-size: 1rem;
  margin-top: 0.2rem;
}
.modal-input:focus {
  outline: none;
  border-color: $green;
  background: #23272f;
}
.modal-close {
  position: absolute;
  top: 0.7rem;
  right: 1.1rem;
  background: none;
  border: none;
  color: #aaa;
  font-size: 2rem;
  cursor: pointer;
  transition: color 0.15s;
  z-index: 10;
}
.modal-close:hover {
  color: $red;
}
</style> 