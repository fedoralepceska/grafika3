<template>
    <div>
        <button @click="openDialog" class="btn create-order1 mr-2">
            {{ $t('edit') }}
        </button>

        <!-- Dialog Overlay -->
        <div v-if="dialog" class="dialog-overlay" @click="closeDialog">
            <!-- Dialog Content -->
            <div class="dialog-content background" @click.stop>
                <div class="dialog-header">
                    <span class="text-h5 text-white">{{ $t('editRole') }}</span>
                </div>
                
                <div class="dialog-body">
                    <form @submit.prevent="saveRole">
                        <div class="form-group">
                            <label for="name" class="text-white">{{ $t('name') }}</label>
                            <input 
                                type="text" 
                                id="name"
                                v-model="form.name"
                                class="rounded text-black"
                                :placeholder="$t('enterRoleName')"
                                required
                            >
                            <p v-if="form.errors.name" class="error-text">{{ form.errors.name }}</p>
                        </div>
                    </form>
                </div>

                <div class="dialog-footer">
                    <button @click="closeDialog" class="btn cancel-btn">
                        {{ $t('cancel') }}
                    </button>
                    <button @click="saveRole" class="btn create-order" :disabled="form.processing">
                        {{ $t('update') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { useToast } from "vue-toastification";
import axios from 'axios';

export default {
    props: {
        role: {
            type: Object,
            required: true
        }
    },
    emits: ['roleUpdated'],
    data() {
        return {
            dialog: false,
            form: {
                name: this.role.name
            }
        };
    },
    methods: {
        openDialog() {
            this.dialog = true;
            document.body.style.overflow = 'hidden';
            this.form.name = this.role.name;
        },
        closeDialog() {
            this.dialog = false;
            document.body.style.overflow = 'auto';
        },
        async saveRole() {
            const toast = useToast();
            
            try {
                const response = await axios.put(`/api/user-roles/${this.role.id}`, {
                    name: this.form.name
                });
                
                toast.success('Role updated successfully!');
                this.closeDialog();
                this.$emit('roleUpdated');
            } catch (error) {
                if (error.response?.data?.errors) {
                    const firstError = Object.values(error.response.data.errors)[0];
                    toast.error(firstError[0]);
                } else {
                    toast.error('Error updating role: ' + error.message);
                }
            }
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape' && this.dialog) {
                this.closeDialog();
            }
        }
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleEscapeKey);
        document.body.style.overflow = 'auto';
    }
};
</script>

<style scoped lang="scss">
.dialog-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.dialog-content {
    width: 100%;
    max-width: 500px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
}

.dialog-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.dialog-body {
    padding: 20px;
}

.dialog-footer {
    padding: 16px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.background {
    background-color: $light-gray;
}

.error-text {
    color: $red;
    font-size: 0.875rem;
}

input {
    padding: 8px;
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    
    &:focus {
        outline: none;
        border-color: $blue;
    }
}

.btn {
    padding: 8px 16px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 4px;
    
    &.create-order {
        background-color: $blue;
        color: white;
        
        &:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
    }
    
    &.cancel-btn {
        background-color: $gray;
        color: white;
    }
}
</style> 