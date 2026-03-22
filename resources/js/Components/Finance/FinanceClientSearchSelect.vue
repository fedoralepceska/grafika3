<template>
    <div class="fc-cs" ref="root">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <button
            type="button"
            class="fc-cs__trigger text-black"
            :class="{ 'fc-cs__trigger--open': open }"
            @click.stop="toggle"
        >
            <span class="fc-cs__trigger-text">{{ displayName }}</span>
            <span class="fc-cs__caret" aria-hidden="true">▾</span>
        </button>
        <div v-if="open" class="fc-cs__dropdown" @click.stop>
            <input
                v-model="clientSearch"
                type="text"
                class="fc-cs__search text-black"
                placeholder="Search clients..."
                autocomplete="off"
                @keydown.esc="open = false"
            />
            <div class="fc-cs__list">
                <button type="button" class="fc-cs__item" @click="select(null)">All clients</button>
                <button
                    v-for="c in filteredClients"
                    :key="c.id"
                    type="button"
                    class="fc-cs__item"
                    :class="{ 'fc-cs__item--active': modelValue === c.id }"
                    @click="select(c.id)"
                >
                    {{ c.name }}
                </button>
                <div v-if="filteredClients.length === 0" class="fc-cs__empty">No matches</div>
            </div>
        </div>
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
    },
    emits: ['update:modelValue', 'change'],
    data() {
        return {
            open: false,
            clientSearch: '',
        };
    },
    computed: {
        displayName() {
            if (this.modelValue == null || this.modelValue === '' || this.modelValue === 'All') {
                return 'All clients';
            }
            const id = Number(this.modelValue);
            const c = this.clients.find((x) => Number(x.id) === id);
            return c ? c.name : 'All clients';
        },
        filteredClients() {
            const q = (this.clientSearch || '').trim().toLowerCase();
            if (!q) {
                return this.clients;
            }
            return this.clients.filter((c) => (c.name || '').toLowerCase().includes(q));
        },
    },
    watch: {
        open(val) {
            if (!val) {
                this.clientSearch = '';
            }
        },
    },
    mounted() {
        document.addEventListener('click', this.onDocClick);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.onDocClick);
    },
    methods: {
        onDocClick(e) {
            const el = this.$refs.root;
            if (el && !el.contains(e.target)) {
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
