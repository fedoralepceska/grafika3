<template>
    <teleport to="body">
    <div class="modal" v-if="showModal" @click.self="closeModal" role="dialog" aria-modal="true">
        <div class="modal-content" tabindex="-1" ref="panel">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>{{ showAllNotes ? 'All Notes' || 'All Notes & Comments' : $t('readNote') }}</span>
                </div>
                <button @click="closeModal" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Order-Level Comment -->
                <div v-if="comment" class="note-section">
                    <h4 class="note-section-title">
                        <i class="fa-solid fa-clipboard-list"></i>
                        {{ showAllNotes ? 'Order Comment' : 'Order Note' }}
                    </h4>
                    <div class="note-content">
                        <p>{{ comment }}</p>
                    </div>
                </div>
                
                <!-- Job-Specific Notes -->
                <div v-if="jobNotes && jobNotes.length > 0" class="note-section">
                    <h4 class="note-section-title">
                        <i class="fa-solid fa-hammer"></i>
                        {{ showAllNotes ? 'All Job Notes' : 'Job-Specific Notes' }}
                        <span v-if="showAllNotes" class="note-count">({{ getGroupedJobNotes().length }} job{{ getGroupedJobNotes().length !== 1 ? 's' : '' }})</span>
                    </h4>
                    
                    <!-- Group notes by job when showing all notes -->
                    <template v-if="showAllNotes">
                        <div v-for="(jobGroup, jobId) in getGroupedJobNotes()" :key="jobId" class="job-note-group">
                            <div class="job-note-group-header">
                                <strong class="job-number">
                                    <i class="fa-solid fa-briefcase"></i>
                                    Job #{{ jobGroup.job_index !== undefined ? jobGroup.job_index + 1 : getJobNumber(Number(jobId)) }}
                                </strong>
                            </div>
                            <div v-for="(note, noteIndex) in jobGroup.notes" :key="`${jobId}-${noteIndex}`" class="job-note-item">
                                <div v-if="note.selected_actions && note.selected_actions.length > 0" class="actions-badges">
                                    <span 
                                        v-for="action in note.selected_actions" 
                                        :key="action" 
                                        class="action-badge"
                                    >
                                        <i class="fa-solid fa-tag"></i>
                                        {{ action }}
                                    </span>
                                </div>
                                <div class="note-content">
                                    <p>{{ note.comment }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Original display for current action notes -->
                    <template v-else>
                        <div v-for="jobNote in jobNotes" :key="jobNote.job_id" class="job-note-item">
                            <div class="job-note-header">
                                <strong>Job #{{ getJobNumber(jobNote.job_id) }}</strong>
                                <span v-if="jobNote.selected_actions && jobNote.selected_actions.length > 0" class="actions-list">
                                    ({{ jobNote.selected_actions.join(', ') }})
                                </span>
                            </div>
                            <div class="note-content">
                                <p>{{ jobNote.comment }}</p>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div v-if="!comment && (!jobNotes || jobNotes.length === 0)" class="note-content">
                    <p class="no-notes">No notes available</p>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeModal">
                    <i class="fa-solid fa-xmark"></i> {{ $t('close') }}
                </button>
                <button v-if="!showAllNotes" class="btn btn-primary" @click="acknowledge">
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
        jobNotes: Array,
        closeModal: Function,
        acknowledge: Function,
        invoice: Object,
        showAllNotes: {
            type: Boolean,
            default: false
        }
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
            // Note: The ActionPage will handle the actual acknowledgment logic
            // We don't clear the invoice comment here since it might be needed for other actions
        },
        getJobNumber(jobId) {
            // First try to find job_index from jobNotes
            const jobNote = this.jobNotes?.find(note => note.job_id === jobId || note.job_id === Number(jobId));
            if (jobNote && jobNote.job_index !== undefined) {
                return jobNote.job_index + 1;
            }
            
            // Fallback to invoice.jobs array
            if (this.invoice && this.invoice.jobs) {
                const jobIdNum = typeof jobId === 'string' ? Number(jobId) : jobId;
                const jobIndex = this.invoice.jobs.findIndex(job => job.id === jobIdNum);
                if (jobIndex >= 0) {
                    return jobIndex + 1;
                }
            }
            
            return '?';
        },
        getGroupedJobNotes() {
            // Group job notes by job_id
            const grouped = {};
            
            if (!this.jobNotes || this.jobNotes.length === 0) {
                return grouped;
            }
            
            this.jobNotes.forEach(note => {
                const jobId = note.job_id;
                if (!grouped[jobId]) {
                    grouped[jobId] = {
                        job_id: jobId,
                        job_index: note.job_index,
                        notes: []
                    };
                }
                grouped[jobId].notes.push(note);
            });
            
            return grouped;
        }
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

.note-section {
    margin-bottom: 16px;
}

.note-section:last-child {
    margin-bottom: 0;
}

.note-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #e2e8f0;
}

.job-note-group {
    margin-bottom: 20px;
    padding: 12px;
    background-color: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
    border-left: 3px solid #2563eb;
}

.job-note-group:last-child {
    margin-bottom: 0;
}

.job-note-group-header {
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.job-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #e2e8f0;
}

.job-note-item {
    margin-bottom: 12px;
}

.job-note-item:last-child {
    margin-bottom: 0;
}

.job-note-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    font-size: 12px;
}

.actions-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 8px;
}

.action-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background-color: rgba(37, 99, 235, 0.2);
    border: 1px solid rgba(37, 99, 235, 0.4);
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    color: #93c5fd;
}

.action-badge i {
    font-size: 10px;
}

.actions-list {
    color: #94a3b8;
    font-weight: normal;
}

.note-count {
    font-size: 12px;
    font-weight: normal;
    color: #94a3b8;
    margin-left: 8px;
}

.note-content {
    background-color: $light-gray;
    border-radius: 6px;
    padding: 16px;
    max-height: 46vh;
    overflow: auto;
    line-height: 1.6;
}

.no-notes {
    color: #94a3b8;
    font-style: italic;
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
