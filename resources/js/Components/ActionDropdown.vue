<template>
    <div class="action-dropdown">
        <button
            ref="triggerRef"
            type="button"
            class="dropdown-trigger"
            :class="[toneClass, { 'icon-only': iconOnly, disabled }]"
            :title="triggerTitle"
            :aria-label="triggerTitle"
            :disabled="disabled"
            @click="toggleMenu"
        >
            <template v-if="iconOnly">
                <i :class="iconClass" aria-hidden="true"></i>
            </template>
            <template v-else>
                <span>{{ label }}</span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </template>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="menuRef"
                class="dropdown-content"
                :style="menuStyles"
            >
                <template v-for="(group, groupIndex) in normalizedGroups" :key="groupIndex">
                    <div v-if="group.label" class="dropdown-label">{{ group.label }}</div>

                    <button
                        v-for="item in group.items"
                        :key="item.value"
                        type="button"
                        class="dropdown-item"
                        :class="{ danger: item.danger, disabled: item.disabled }"
                        :disabled="item.disabled"
                        @click="selectItem(item)"
                    >
                        <span>{{ item.label }}</span>
                    </button>

                    <div
                        v-if="groupIndex < normalizedGroups.length - 1"
                        class="dropdown-separator"
                    ></div>
                </template>
            </div>
        </Teleport>
    </div>
</template>

<script>
export default {
    props: {
        label: {
            type: String,
            default: 'Actions',
        },
        groups: {
            type: Array,
            default: () => [],
        },
        width: {
            type: Number,
            default: 180,
        },
        iconOnly: {
            type: Boolean,
            default: false,
        },
        iconClass: {
            type: String,
            default: 'fa-solid fa-ellipsis-vertical',
        },
        triggerTitle: {
            type: String,
            default: 'Open actions menu',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        tone: {
            type: String,
            default: 'neutral',
        },
    },
    emits: ['select'],
    data() {
        return {
            isOpen: false,
            menuStyles: {},
        };
    },
    computed: {
        normalizedGroups() {
            return this.groups.filter(group => group?.items?.length);
        },
        toneClass() {
            const classes = {
                neutral: 'tone-neutral',
                success: 'tone-success',
                danger: 'tone-danger',
            };

            return classes[this.tone] || classes.neutral;
        },
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscape);
        window.addEventListener('resize', this.updatePosition);
        window.addEventListener('scroll', this.updatePosition, true);
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleEscape);
        document.removeEventListener('click', this.handleOutsideClick, true);
        window.removeEventListener('resize', this.updatePosition);
        window.removeEventListener('scroll', this.updatePosition, true);
    },
    methods: {
        toggleMenu() {
            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                this.$nextTick(() => {
                    this.updatePosition();
                    document.addEventListener('click', this.handleOutsideClick, true);
                });
            } else {
                document.removeEventListener('click', this.handleOutsideClick, true);
            }
        },
        closeMenu() {
            this.isOpen = false;
            document.removeEventListener('click', this.handleOutsideClick, true);
        },
        selectItem(item) {
            if (item.disabled) return;
            this.$emit('select', item);
            this.closeMenu();
        },
        handleOutsideClick(event) {
            const trigger = this.$refs.triggerRef;
            const menu = this.$refs.menuRef;

            if (trigger?.contains(event.target) || menu?.contains(event.target)) {
                return;
            }

            this.closeMenu();
        },
        handleEscape(event) {
            if (event.key === 'Escape') {
                this.closeMenu();
            }
        },
        updatePosition() {
            if (!this.isOpen || !this.$refs.triggerRef) return;

            const rect = this.$refs.triggerRef.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const width = this.width;
            const left = Math.min(
                Math.max(12, rect.right - width),
                viewportWidth - width - 12
            );

            this.menuStyles = {
                position: 'fixed',
                top: `${rect.bottom + 8}px`,
                left: `${left}px`,
                width: `${width}px`,
                zIndex: 9999,
            };
        },
    },
};
</script>

<style scoped lang="scss">
.dropdown-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 6px 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: $white;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
}

.dropdown-trigger:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.24);
}

.dropdown-trigger.disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.dropdown-trigger.icon-only {
    width: 34px;
    height: 34px;
    padding: 0;
}

.dropdown-trigger.tone-success {
    border-color: rgba(74, 222, 128, 0.32);
    background: rgba(74, 222, 128, 0.12);
}

.dropdown-trigger.tone-success:hover:not(.disabled) {
    background: rgba(74, 222, 128, 0.18);
    border-color: rgba(74, 222, 128, 0.46);
}

.dropdown-trigger.tone-danger {
    border-color: rgba(248, 113, 113, 0.3);
    background: rgba(248, 113, 113, 0.1);
}

.dropdown-trigger.tone-danger:hover:not(.disabled) {
    background: rgba(248, 113, 113, 0.16);
    border-color: rgba(248, 113, 113, 0.44);
}

.dropdown-content {
    padding: 8px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    background: #1f2937;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.32);
}

.dropdown-label {
    padding: 6px 8px 8px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.dropdown-item {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 10px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: $white;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    transition: background 0.2s ease, color 0.2s ease;
}

.dropdown-item:hover:not(.disabled) {
    background: rgba(255, 255, 255, 0.08);
}

.dropdown-item.danger {
    color: #f87171;
}

.dropdown-item.disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.dropdown-separator {
    height: 1px;
    margin: 8px 0;
    background: rgba(255, 255, 255, 0.08);
}
</style>
