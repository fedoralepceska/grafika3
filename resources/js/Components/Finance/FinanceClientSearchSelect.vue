<template>
    <div class="fc-cs" ref="root">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <button
            ref="trigger"
            type="button"
            class="fc-cs__trigger text-black"
            :class="{ 'fc-cs__trigger--open': open }"
            @click.stop="toggle"
        >
            <span class="fc-cs__trigger-text">{{ displayName }}</span>
            <span class="fc-cs__caret" aria-hidden="true">▾</span>
        </button>
        <teleport to="body" :disabled="!teleportDropdown">
            <div
                v-if="open"
                ref="dropdownPanel"
                class="fc-cs__dropdown"
                :class="{ 'fc-cs__dropdown--teleport': teleportDropdown }"
                :style="teleportDropdown ? teleportStyle : undefined"
                @click.stop
            >
            <input
                ref="searchInput"
                v-model="clientSearch"
                type="text"
                class="fc-cs__search text-black"
                placeholder="Search clients..."
                autocomplete="off"
                @keydown.esc="open = false"
            />
            <div class="fc-cs__list">
                <button v-if="variant === 'filter'" type="button" class="fc-cs__item" @click="select(null)">
                    All clients
                </button>
                <button v-else type="button" class="fc-cs__item" @click="select(null)">
                    {{ emptyLabel }}
                </button>
                <button
                    v-for="c in filteredClients"
                    :key="c.id"
                    type="button"
                    class="fc-cs__item"
                    :class="{ 'fc-cs__item--active': Number(modelValue) === Number(c.id) }"
                    @click="select(c.id)"
                >
                    {{ c.name }}
                </button>
                <div v-if="filteredClients.length === 0" class="fc-cs__empty">No matches</div>
            </div>
            </div>
        </teleport>
    </div>
</template>

<script>
export default {
    name: 'FinanceClientSearchSelect',
    props: {
        clients: { type: Array, default: () => [] },
        /** null = all clients */
        modelValue: { type: [Number, String, null], default: null },
        label: { type: String, default: 'Client' },
        /**
         * filter: null means "all clients" (finance lists).
         * pick: null means nothing selected; first row clears to null (forms).
         */
        variant: {
            type: String,
            default: 'filter',
            validator: (v) => ['filter', 'pick'].includes(v),
        },
        /** Label for null selection when variant is pick */
        emptyLabel: { type: String, default: 'Select client' },
        /** Avoid clipping inside overflow scroll (e.g. v-dialog); positions panel fixed under trigger */
        teleportDropdown: { type: Boolean, default: false },
    },
    emits: ['update:modelValue', 'change'],
    data() {
        return {
            open: false,
            clientSearch: '',
            teleportStyle: {},
        };
    },
    computed: {
        displayName() {
            if (this.modelValue == null || this.modelValue === '' || this.modelValue === 'All') {
                return this.variant === 'pick' ? this.emptyLabel : 'All clients';
            }
            const id = Number(this.modelValue);
            const c = this.clients.find((x) => Number(x.id) === id);
            if (c) {
                return c.name;
            }
            return this.variant === 'pick' ? this.emptyLabel : 'All clients';
        },
        filteredClients() {
            const q = (this.clientSearch || '').trim().toLowerCase();
            const list = !q
                ? [...this.clients]
                : this.clients.filter((c) => (c.name || '').toLowerCase().includes(q));
            list.sort((a, b) =>
                String(a.name || '').localeCompare(String(b.name || ''), undefined, { sensitivity: 'base' }),
            );
            return list;
        },
    },
    watch: {
        open(val) {
            if (!val) {
                this.clientSearch = '';
                this.teardownTeleportListeners();
                return;
            }
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
                if (this.teleportDropdown) {
                    this.syncTeleportPosition();
                    window.addEventListener('scroll', this.onTeleportReposition, true);
                    window.addEventListener('resize', this.onTeleportReposition);
                }
            });
        },
    },
    mounted() {
        document.addEventListener('click', this.onDocClick);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.onDocClick);
        this.teardownTeleportListeners();
    },
    methods: {
        onTeleportReposition() {
            if (this.open && this.teleportDropdown) {
                this.syncTeleportPosition();
            }
        },
        syncTeleportPosition() {
            const btn = this.$refs.trigger;
            if (!btn) {
                return;
            }
            const r = btn.getBoundingClientRect();
            this.teleportStyle = {
                position: 'fixed',
                top: `${Math.round(r.bottom + 4)}px`,
                left: `${Math.round(r.left)}px`,
                width: `${Math.round(r.width)}px`,
                /* Above typical Vuetify overlays (~2.4k) when teleport is used outside dialog */
                zIndex: 10000,
            };
        },
        teardownTeleportListeners() {
            window.removeEventListener('scroll', this.onTeleportReposition, true);
            window.removeEventListener('resize', this.onTeleportReposition);
        },
        onDocClick(e) {
            const root = this.$refs.root;
            const panel = this.$refs.dropdownPanel;
            const inside = (root && root.contains(e.target)) || (panel && panel.contains(e.target));
            if (!inside) {
                this.open = false;
            }
        },
        toggle() {
            this.open = !this.open;
        },
        select(id) {
            this.$emit('update:modelValue', id);
            this.$emit('change');
            this.open = false;
            this.clientSearch = '';
        },
    },
};
</script>

<style scoped lang="scss">
.fc-cs {
    position: relative;
    min-width: 0;
}

.fc-label {
    display: block;
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.7);
}

.fc-cs__trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 34px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    text-align: left;
}

.fc-cs__trigger--open {
    border-color: rgba(59, 130, 246, 0.6);
}

.fc-cs__trigger-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.fc-cs__caret {
    flex-shrink: 0;
    margin-left: 8px;
    font-size: 10px;
    opacity: 0.6;
}

.fc-cs__dropdown {
    position: absolute;
    z-index: 50;
    left: 0;
    right: 0;
    margin-top: 4px;
    background: #1f2937;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    overflow: hidden;
}

.fc-cs__dropdown--teleport {
    right: auto;
    margin-top: 0;
}

.fc-cs__search {
    width: 100%;
    padding: 8px 10px;
    border: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 13px;
}

.fc-cs__list {
    max-height: 220px;
    overflow-y: auto;
}

.fc-cs__item {
    display: block;
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: transparent;
    color: rgba(255, 255, 255, 0.92);
    font-size: 13px;
    text-align: left;
    cursor: pointer;
}

.fc-cs__item:hover {
    background: rgba(59, 130, 246, 0.2);
}

.fc-cs__item--active {
    background: rgba(59, 130, 246, 0.28);
}

.fc-cs__empty {
    padding: 12px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.45);
}
</style>
