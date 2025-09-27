<template>
  <div v-if="visible" class="custom-modal-backdrop" @click.self="close">
    <div class="custom-modal-content system-modal">
      <div class="custom-modal-header system-modal-header">
        <h2 class="system-modal-title">Before Production Questions</h2>
        <button class="custom-modal-close system-modal-close" @click="close">&times;</button>
      </div>
      <div class="custom-modal-body system-modal-body">
        <div v-for="item in catalogItems" :key="item.id" class="mb-4 border border-[#2a3946] p-2 rounded-lg shadow-lg">
          <h3 class="font-bold mb-4 text-lg text-white flex items-center gap-1">
            <span class="catalog-item-dot"></span>{{ item.name }}
          </h3>
          <div v-if="questionsByCatalogItem[item.id] && questionsByCatalogItem[item.id].length">
            <div v-for="question in questionsByCatalogItem[item.id]" :key="question.id" class="mb-2">
              <label class="modern-checkbox-label flex items-center gap-3 p-2 rounded-lg bg-[#23272f] border border-[#444] hover:border-[#81c950] transition cursor-pointer">
                <input type="checkbox" v-model="answers[item.id][question.id]" class="modern-checkbox" />
                <span class="text-white text-base">{{ question.question }}</span>
              </label>
            </div>
          </div>
          <div v-else class="no-questions-message text-gray-400 bg-[#23272f] border border-[#444] rounded-lg p-4 text-center">
            <span>No questions required for this item.</span>
          </div>
        </div>
      </div>
      <div class="flex justify-end custom-modal-footer system-modal-footer">
        <button class="btn btn-secondary system-btn-secondary" @click="close">Cancel</button>
        <button class="btn btn-primary ml-2 system-btn" @click="submit" :disabled="isCreatingJobs">Proceed</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'QuestionsModal',
  props: {
    visible: { type: Boolean, default: false },
    questionsByCatalogItem: { type: Object, required: true },
    catalogItems: { type: Array, required: true },
    isCreatingJobs: { type: Boolean, default: false }
  },
  data() {
    return {
      answers: {}
    };
  },
  watch: {
    visible(val) {
      if (val) {
        this.initAnswers();
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    }
  },
  mounted() {
    if (this.visible) this.initAnswers();
    if (this.visible) document.body.style.overflow = 'hidden';
  },
  beforeUnmount() {
    document.body.style.overflow = '';
  },
  methods: {
    initAnswers() {
      this.answers = {};
      for (const item of this.catalogItems) {
        this.answers[item.id] = {};
        const questions = this.questionsByCatalogItem[item.id] || [];
        for (const q of questions) {
          this.answers[item.id][q.id] = false;
        }
      }
    },
    submit() {
      // Only include checked answers with the question text
      const result = {};
      for (const item of this.catalogItems) {
        const questions = this.questionsByCatalogItem[item.id] || [];
        result[item.id] = {};
        for (const q of questions) {
          if (this.answers[item.id][q.id]) {
            result[item.id][q.id] = {
              question: q.question,
              answer: true // Just mark that the question was answered
            };
          }
        }
        // If no answers for this item, remove the key
        if (Object.keys(result[item.id]).length === 0) {
          delete result[item.id];
        }
      }
      this.$emit('submit-answers', result);
      this.close();
    },
    close() {
      this.$emit('close');
    }
  }
};
</script>

<style scoped lang="scss">
.system-modal {
  background: $light-gray;
  color: #fff;
  box-shadow: 0 4px 32px rgba(0,0,0,0.25);
  min-width: 100vh;
  max-width: 150vw;
  max-height: 90vh;
  overflow-y: auto;
  padding: 0;
  position: relative;
}
.system-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.2rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #444;
}
.system-modal-title {
  font-size: 1.3rem;
  font-weight: bold;
  color: #fff;
}
.system-modal-close {
  background: none;
  border: none;
  font-size: 2rem;
  color: #aaa;
  cursor: pointer;
  line-height: 1;
  transition: color 0.15s;
}
.system-modal-close:hover {
  color: $red;
}
.system-modal-body {
  padding: 2rem 2.5rem 0rem 2.5rem;
}
.system-modal-footer {
  padding: 1rem 2.5rem 1.5rem 2.5rem;
}
.btn {
  padding: 10px 24px;
  border: none;
  cursor: pointer;
  font-weight: bold;
  border-radius: 5px;
  font-size: 1rem;
  transition: background 0.2s;
}
.btn-primary, .system-btn {
  background-color: #408a0b;
  color: #fff;
}
.btn-primary:disabled, .system-btn:disabled {
  background-color: #6fae4a;
  cursor: not-allowed;
}
.btn-primary:hover:not(:disabled), .system-btn:hover:not(:disabled) {
  background-color: #81c950;
}
.btn-secondary, .system-btn-secondary {
  background-color: $red;
  color: #fff;
}
.btn-secondary:hover, .system-btn-secondary:hover {
  background-color: rgba($red, 0.8);
}
.custom-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  backdrop-filter: blur(10px);
}
.catalog-item-dot {
  width: 12px;
  height: 12px;
  background: #81c950;
  border-radius: 50%;
  display: inline-block;
}
.modern-checkbox-label {
  background: #23272f;
  border: 2px solid #444;
  padding: 1.1rem 1rem;
  border-radius: 10px;
  font-size: 1.1rem;
  transition: border 0.2s, box-shadow 0.2s;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  cursor: pointer;
  user-select: none;
  margin-bottom: 0.2rem;
}
.modern-checkbox-label:hover, .modern-checkbox:focus + span {
  border-color: #81c950;
}
.modern-checkbox {
  width: 22px;
  height: 22px;
  accent-color: #81c950;
  border-radius: 6px;
  margin-right: 0.7rem;
  transition: box-shadow 0.2s;
}
.no-questions-message {
  font-size: 1.08rem;
  color: #b0b0b0;
  background: #23272f;
  border: 1.5px dashed #444;
  border-radius: 10px;
  padding: 1.2rem 0.5rem;
  margin-top: 0.5rem;
}
</style> 