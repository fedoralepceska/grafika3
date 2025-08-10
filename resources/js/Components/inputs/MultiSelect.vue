<template>
  <div class="relative" ref="root">
    <!-- Control -->
    <button
      type="button"
      class="w-full min-h-[40px] bg-gray-700 text-white rounded px-3 py-2 flex items-center justify-between border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500"
      :class="{ 'opacity-60 cursor-not-allowed': disabled }"
      @click="toggle"
      :disabled="disabled"
      aria-haspopup="listbox"
      :aria-expanded="open.toString()"
    >
      <div class="flex flex-wrap gap-1 items-center text-left">
        <template v-if="selectedOptions.length === 0">
          <span class="text-gray-300">{{ placeholder }}</span>
        </template>
        <template v-else>
          <template v-for="(opt, idx) in visibleChips" :key="getValue(opt)">
            <span class="bg-gray-600 text-white text-xs rounded px-2 py-1 flex items-center gap-1">
              {{ getLabel(opt) }}
              <i class="fas fa-times cursor-pointer" @click.stop="remove(opt)"></i>
            </span>
          </template>
          <span v-if="hiddenCount > 0" class="text-xs text-gray-300">+{{ hiddenCount }}</span>
        </template>
      </div>
      <i class="fas fa-chevron-down text-gray-300 ml-2"></i>
    </button>

    <!-- Dropdown -->
    <transition name="fade">
      <div
        v-if="open"
        class="absolute z-50 mt-1 w-full bg-gray-800 border border-gray-600 rounded shadow-lg overflow-hidden"
        role="listbox"
      >
        <div class="p-2 border-b border-gray-700 bg-gray-800">
          <input
            v-model="query"
            type="text"
            class="w-full bg-gray-700 text-white rounded px-3 py-2 text-sm border border-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500"
            :placeholder="searchPlaceholder"
          />
          <div class="flex justify-between items-center mt-2 text-xs text-gray-300">
            <button type="button" class="hover:text-white" @click="selectAllFiltered">Select all</button>
            <button type="button" class="hover:text-white" @click="clearAll">Clear</button>
          </div>
        </div>
        <div class="max-h-64 overflow-auto">
          <div
            v-for="opt in filteredOptions"
            :key="getValue(opt)"
            class="px-3 py-2 hover:bg-gray-700 flex items-center gap-2 cursor-pointer"
            @click.stop="toggleOption(opt)"
          >
            <input type="checkbox" class="accent-green-500" :checked="isSelected(opt)" @change.prevent />
            <span class="text-white text-sm">{{ getLabel(opt) }}</span>
          </div>
          <div v-if="filteredOptions.length === 0" class="px-3 py-3 text-sm text-gray-400">No results</div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'MultiSelect',
  props: {
    modelValue: { type: Array, default: () => [] },
    options: { type: Array, default: () => [] },
    labelKey: { type: String, default: 'name' },
    valueKey: { type: String, default: 'id' },
    placeholder: { type: String, default: 'Select options' },
    searchPlaceholder: { type: String, default: 'Search…' },
    disabled: { type: Boolean, default: false },
    maxChips: { type: Number, default: 3 },
  },
  emits: ['update:modelValue', 'change'],
  data() {
    return {
      open: false,
      query: '',
    };
  },
  computed: {
    selectedOptions() {
      const values = new Set(this.modelValue);
      return this.options.filter((o) => values.has(this.getValue(o)));
    },
    filteredOptions() {
      if (!this.query) return this.options;
      const q = this.query.toLowerCase();
      return this.options.filter((o) => String(this.getLabel(o)).toLowerCase().includes(q));
    },
    visibleChips() {
      return this.selectedOptions.slice(0, this.maxChips);
    },
    hiddenCount() {
      const extra = this.selectedOptions.length - this.maxChips;
      return extra > 0 ? extra : 0;
    },
  },
  methods: {
    getLabel(opt) {
      return opt?.[this.labelKey] ?? '';
    },
    getValue(opt) {
      return opt?.[this.valueKey];
    },
    isSelected(opt) {
      return this.modelValue.includes(this.getValue(opt));
    },
    toggle() {
      if (this.disabled) return;
      this.open = !this.open;
    },
    close() {
      this.open = false;
      this.query = '';
    },
    toggleOption(opt) {
      const value = this.getValue(opt);
      const next = this.isSelected(opt)
        ? this.modelValue.filter((v) => v !== value)
        : [...this.modelValue, value];
      this.$emit('update:modelValue', next);
      this.$emit('change', next);
    },
    remove(opt) {
      const value = this.getValue(opt);
      const next = this.modelValue.filter((v) => v !== value);
      this.$emit('update:modelValue', next);
      this.$emit('change', next);
    },
    selectAllFiltered() {
      const filteredValues = this.filteredOptions.map((o) => this.getValue(o));
      const set = new Set([...(this.modelValue || []), ...filteredValues]);
      const next = Array.from(set);
      this.$emit('update:modelValue', next);
      this.$emit('change', next);
    },
    clearAll() {
      this.$emit('update:modelValue', []);
      this.$emit('change', []);
    },
    onClickOutside(e) {
      if (!this.$refs.root) return;
      if (!this.$refs.root.contains(e.target)) this.close();
    },
  },
  mounted() {
    document.addEventListener('click', this.onClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onClickOutside);
  },
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .12s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>


