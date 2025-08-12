<template>
  <div v-if="visible" class="custom-modal-backdrop" @click.self="onCancel">
    <div class="custom-modal-content system-modal">
      <div class="custom-modal-header system-modal-header">
        <h2 class="system-modal-title">Leave page?</h2>
        <button class="custom-modal-close system-modal-close" @click="onCancel">&times;</button>
      </div>
      <div class="custom-modal-body system-modal-body text-white">
        <p class="mb-4">
          You have <strong>{{ jobCount }}</strong> temporary job<span v-if="jobCount !== 1">s</span> created for this invoice that are not linked yet.
        </p>
        <div class="bg-[#23272f] border border-[#444] rounded-lg p-4 mb-4">
          <p class="font-semibold mb-2">If you leave now:</p>
          <ul class="list-disc ml-5 space-y-1">
            <li>All temporary jobs will be deleted to free storage (including uploaded files in R2).</li>
            <li>No data from this draft will be saved.</li>
          </ul>
        </div>
        <p class="mt-2 text-yellow-300">Are you sure you want to leave this page?</p>
      </div>
      <div class="flex justify-end custom-modal-footer system-modal-footer">
        <button class="btn btn-secondary system-btn-secondary" @click="onCancel" :disabled="loading">Stay</button>
        <button class="btn btn-primary ml-2 system-btn" @click="onConfirm" :disabled="loading">
          <span v-if="loading">Deleting…</span>
          <span v-else>Delete and leave</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmLeaveJobsModal',
  props: {
    visible: { type: Boolean, default: false },
    jobCount: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
  },
  emits: ['close', 'confirm'],
  methods: {
    onCancel() {
      this.$emit('close');
    },
    onConfirm() {
      this.$emit('confirm');
    },
  },
};
</script>

<style scoped lang="scss">
.system-modal { background: $light-gray; color: #fff; box-shadow: 0 4px 32px rgba(0,0,0,0.25); min-width: 60vh; max-width: 90vw; max-height: 90vh; overflow-y: auto; padding: 0; position: relative; }
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


