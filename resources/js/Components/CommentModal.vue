<template>
    <teleport to="body">
    <div class="modal" v-if="showModal" @click.self="closeModal" role="dialog" aria-modal="true">
        <div class="modal-content" tabindex="-1" ref="panel">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>{{ $t('readNote') }}</span>
                </div>
                <button @click="closeModal" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="note-content">
                    <p>{{ comment }}</p>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">
                    <i class="fa-solid fa-xmark"></i> {{ $t('close') }}
                </button>
                <button class="btn btn-primary" @click="acknowledge">
                    {{ $t('acknowledge') }} <i class="fa-solid fa-check"></i>
                </button>
            </div>
        </div>
    </div>
    </teleport>
</template>

<script>
import axios from "axios";

export default {
    props: {
        showModal: Boolean,
        comment: String,
        closeModal: Function,
        acknowledge: Function,
        invoice: Object
    },

    mounted() {
        try {
            document.addEventListener('keydown', this.onKeyDown);
            this.$nextTick(() => {
                this.$refs.panel && this.$refs.panel.focus();
            });
        } catch (e) {}
    },
    beforeUnmount() {
        try { document.removeEventListener('keydown', this.onKeyDown); } catch (e) {}
    },
    methods: {
        onKeyDown(e) {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        },
        acknowledge() {
            // params: showModal, acknowledged
            this.$emit('modal', [false, true]);
            axios.put(`/orders/${this.invoice.id}`, {
                comment: null,
            });
        },
    }
};
</script>

<style scoped lang="scss">
/* Professional modal styling */
.modal {
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 16px;
    z-index: 2000;
}

.modal-content {
    background: $background-color;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    width: 100%;
    max-width: 720px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
    animation: modalIn 160ms ease-out;
    outline: none;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
}

.modal-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.modal-body {
    padding: 16px;
}

.note-content {
    background-color: $light-gray;
    border-radius: 6px;
    padding: 16px;
    max-height: 46vh;
    overflow: auto;
    line-height: 1.6;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 16px 16px 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.btn {
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    cursor: pointer;
}

.btn-secondary {
    background-color: #4a5568;
    color: white;
}

.btn-secondary:hover {
    background-color: #3b475a;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

@keyframes modalIn {
    0% { transform: translateY(10px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}
</style>
