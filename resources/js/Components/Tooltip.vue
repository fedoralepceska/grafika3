<template>
  <span
    class="relative inline-block"
    @mouseenter="show = true"
    @mouseleave="show = false"
    @focusin="show = true"
    @focusout="show = false"
  >
    <slot />
    <transition name="fade">
      <div
        v-if="show && content"
        class="absolute z-[9999] bg-gray-900 text-white text-xs rounded px-3 py-2 shadow-lg border border-gray-700 whitespace-normal break-words pointer-events-none"
        :class="positionClasses"
        :style="{ maxWidth }"
        role="tooltip"
      >
        <div v-if="title" class="text-gray-300 font-semibold mb-1 text-[11px] tracking-wide">{{ title }}</div>
        <div>{{ content }}</div>
        <div
          class="w-3 h-3 bg-gray-900 border border-gray-700 absolute transform rotate-45"
          :class="arrowClasses"
        />
      </div>
    </transition>
  </span>
</template>

<script>
export default {
  name: 'Tooltip',
  props: {
    content: { type: String, default: '' },
    title: { type: String, default: '' },
    placement: { type: String, default: 'top' }, // 'top' | 'bottom' | 'left' | 'right'
    maxWidth: { type: String, default: '16rem' },
  },
  data() {
    return { show: false };
  },
  computed: {
    positionClasses() {
      switch (this.placement) {
        case 'bottom':
          return 'top-full left-1/2 -translate-x-1/2 mt-2';
        case 'left':
          return 'top-1/2 -translate-y-1/2 right-full mr-2';
        case 'right':
          return 'top-1/2 -translate-y-1/2 left-full ml-2';
        case 'top':
        default:
          return 'bottom-full left-1/2 -translate-x-1/2 mb-2';
      }
    },
    arrowClasses() {
      switch (this.placement) {
        case 'bottom':
          return '-top-1 left-1/2 -translate-x-1/2 border-t-0 border-l-0';
        case 'left':
          return 'right-0 -mr-1 top-1/2 -translate-y-1/2 border-t-0 border-r-0';
        case 'right':
          return 'left-0 -ml-1 top-1/2 -translate-y-1/2 border-b-0 border-l-0';
        case 'top':
        default:
          return '-bottom-1 left-1/2 -translate-x-1/2 border-b-0 border-r-0';
      }
    },
  },
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .12s ease, transform .12s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(2px); }
</style>


