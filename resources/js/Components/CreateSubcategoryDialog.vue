<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="400"
            class="height"
            @keydown.esc="closeDialog"
        >
            <template v-slot:activator="{ props }">
                <!-- <button v-bind="props" class="btn btn-secondary">{{ $t('createNewSubcategory') }}</button> -->
                <button 
                    v-bind="props" 
                    class="px-2 py-1 text-green-500"
                    type="button"
                    @click.stop
                > 
                    <span class="fa fa-plus"></span> 
                </button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">{{ $t('createNewSubcategory') }}</span>
                </v-card-title>
                <v-card-text>
                    <div class="form-group">
                        <label class="text-white width100">{{ $t('name') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="rounded text-black"
                            required
                        />
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        {{ $t('cancel') }}
                    </SecondaryButton>
                    <SecondaryButton 
                        @click="submitForm" 
                        class="green"
                        :disabled="!isValid || loading"
                    >
                        {{ loading ? $t('creating') : $t('create') }}
                    </SecondaryButton>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-row>
</template>

<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import { useToast } from 'vue-toastification';

export default {
    components: {
        SecondaryButton
    },
    
    emits: ['created'],
    
    data() {
        return {
            dialog: false,
            form: {
                name: ''
            },
            loading: false
        }
    },

    computed: {
        isValid() {
            return this.form.name.trim() !== '';
        }
    },
    
    methods: {
        closeDialog() {
            this.dialog = false;
            this.form.name = '';
        },
        
        async submitForm() {
            if (!this.isValid) return;

            const toast = useToast();
            this.loading = true;
            
            try {
                const response = await axios.post(route('subcategories.store'), this.form);
                toast.success(this.$t('subcategoryCreated'));
                this.$emit('created', response.data);
                this.closeDialog();
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error(this.$t('errorCreatingSubcategory'));
                }
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<style scoped lang="scss">
.height {
    height: calc(100vh - 400px);
}
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 450px;
    color: $white;
    padding-bottom: 12px;
}
.width100 {
    width: 120px;
}
.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-between;
}
.red {
    background-color: $red;
    color: white;
    border: none;
}
.green {
    background-color: $green;
    color: white;
    border: none;

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    color: white;
}
</style> 