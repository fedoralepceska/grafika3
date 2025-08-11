<template>
  <div v-if="visible" class="custom-modal-backdrop" @click.self="close">
    <div class="custom-modal-content system-modal">
      <div class="custom-modal-header system-modal-header">
        <h2 class="system-modal-title">Delete Refinement</h2>
        <button class="custom-modal-close system-modal-close" @click="close">&times;</button>
      </div>
      <div class="custom-modal-body system-modal-body text-white">
        <p class="mb-4">You are about to delete the refinement <strong>{{ refinement?.name }}</strong>.</p>
        <div class="bg-[#23272f] border border-[#444] rounded-lg p-4 mb-4">
          <p class="font-semibold mb-2">Impact:</p>
          <ul class="list-disc ml-5 space-y-1">
            <li v-if="usage.active_job_actions > 0">
              {{ usage.active_job_actions }} active job action(s) currently use this refinement. Deleting may break in-progress workflows.
            </li>
            <li>
              {{ usage.job_actions_total }} total job action(s) recorded with this refinement.
            </li>
            <li v-if="usage.catalog_items > 0">
              {{ usage.catalog_items }} catalog item(s) reference this refinement in their default actions.
            </li>
          </ul>
        </div>
        <p>
          This operation performs a soft delete. You can restore the refinement later, but references in catalog items and historical data will remain.
        </p>
        <p class="mt-2 text-yellow-300">Only proceed if you are completely sure.</p>
      </div>
      <div class="flex justify-end custom-modal-footer system-modal-footer">
        <button class="btn btn-secondary system-btn-secondary" @click="close">Cancel</button>
        <button class="btn btn-primary ml-2 system-btn" @click="confirm" :disabled="loading">Delete</button>
      </div>
    </div>
  </div>
  
</template>

<script>
import axios from 'axios';

export default {
  name: 'ConfirmRefinementDelete',
  props: {
    visible: { type: Boolean, default: false },
    refinement: { type: Object, default: null },
  },
  data() {
    return {
      loading: false,
      usage: { active_job_actions: 0, job_actions_total: 0, catalog_items: 0 },
    };
  },
  watch: {
    visible(val) {
      if (val && this.refinement?.id) {
        this.fetchUsage();
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    }
  },
  methods: {
    async fetchUsage() {
      try {
        const { data } = await axios.get(`/refinements/${this.refinement.id}/usage`);
        this.usage = data.usage || this.usage;
      } catch (e) {
        // fallback silently
      }
    },
    close() {
      this.$emit('close');
    },
    async confirm() {
      if (!this.refinement) return;
      this.loading = true;
      try {
        await axios.delete(`/refinements/${this.refinement.id}`);
        this.$emit('confirmed');
        this.close();
      } catch (e) {
        alert('Failed to delete refinement.');
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped lang="scss">
.system-modal { background: $light-gray; color: #fff; box-shadow: 0 4px 32px rgba(0,0,0,0.25); min-width: 70vh; max-width: 90vw; max-height: 90vh; overflow-y: auto; padding: 0; position: relative; }
.system-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.2rem 1.5rem 1rem 1.5rem; border-bottom: 1px solid #444; }
.system-modal-title { font-size: 1.2rem; font-weight: bold; color: #fff; }
.system-modal-close { background: none; border: none; font-size: 2rem; color: #aaa; cursor: pointer; line-height: 1; transition: color 0.15s; }
.system-modal-close:hover { color: $red; }
.system-modal-body { padding: 1.5rem 1.8rem 0.5rem 1.8rem; }
.system-modal-footer { padding: 1rem 1.8rem 1.5rem 1.8rem; }
.custom-modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 3000; backdrop-filter: blur(10px); }
.btn { padding: 10px 24px; border: none; cursor: pointer; font-weight: bold; border-radius: 5px; font-size: 1rem; transition: background 0.2s; }
.btn-primary { background-color: #c53030; color: #fff; }
.btn-primary:disabled { background-color: #e53e3e; cursor: not-allowed; }
.btn-primary:hover:not(:disabled) { background-color: #9b2c2c; }
.btn-secondary { background-color: #718096; color: #fff; }
.btn-secondary:hover { background-color: #4a5568; }
</style>


