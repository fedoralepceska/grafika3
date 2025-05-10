<template>
    <MainLayout>
        <div class="px-8 py-6">
            <Header title="Account Creation" subtitle="Create new user accounts" icon="UserLogo.png" link="account-creation"/>
            <div class="dark-gray shadow-xl overflow-hidden p-5">
            <div class="light-gray ">
                <div class="p-6 border-b border-gray-700">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-user-plus mr-3 "></i>
                        Create New Account
                    </h2>
                </div>
                
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Left column -->
                            <div class="space-y-5">
                                <div class="form-group">
                                    <InputLabel for="name" value="Full Name" class="text-white form-label" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        class="mt-1 block w-full text-black rounded-md shadow-sm transition duration-200"
                                        v-model="form.name"
                                        placeholder="Enter user's full name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <div class="form-group">
                                    <InputLabel for="email" value="Email Address" class="text-white form-label" />
                                    <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full text-black rounded-md shadow-sm transition duration-200"
                                        v-model="form.email"
                                        placeholder="Enter user's email address"
                                        required
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                            </div>
                            
                            <!-- Right column -->
                            <div class="space-y-5">
                                <div class="form-group">
                                    <InputLabel for="password" value="Password" class="text-white form-label" />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        class="mt-1 block w-full text-black rounded-md shadow-sm transition duration-200"
                                        v-model="form.password"
                                        placeholder="Enter secure password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div class="form-group">
                                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-white form-label" />
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        class="mt-1 block w-full text-black rounded-md shadow-sm transition duration-200"
                                        v-model="form.password_confirmation"
                                        placeholder="Confirm password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group pt-2">
                            <InputLabel for="role" value="Select Role" class="text-white form-label" />
                            <select
                                id="role"
                                v-model="form.role_id"
                                class="mt-1 block w-full rounded-md text-black px-4 py-2.5 border border-gray-300 bg-white shadow-sm focus:border-green focus:ring focus:ring-green focus:ring-opacity-50 transition duration-200"
                            >
                                <option value="">-- Select a role --</option>
                                <option 
                                    v-for="role in roles" 
                                    :key="role.id" 
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role_id" />
                        </div>

                        <div class="flex items-center justify-end pt-4 border-t border-gray-700">
                            <button 
                                type="button" 
                                class="px-4 py-2 mr-3 rounded-md text-white border border-gray-600 hover:bg-gray-700 transition duration-200"
                                @click="resetForm"
                            >
                                Reset Form
                            </button>
                            <PrimaryButton 
                                :class="{ 'opacity-75 cursor-not-allowed': form.processing }" 
                                :disabled="form.processing"
                                class="px-6"
                            >
                                <i class="fas fa-user-plus mr-2"></i>
                                Create Account
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import InputError from '@/Components/inputs/InputError.vue';
import InputLabel from '@/Components/inputs/InputLabel.vue';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';
import TextInput from '@/Components/inputs/TextInput.vue';
import { useToast } from "vue-toastification";
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';

const roles = ref([]);
const toast = useToast();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: ''
});

const fetchRoles = async () => {
    try {
        const response = await axios.get('/api/user-roles');
        roles.value = response.data;
    } catch (error) {
        console.error('Error fetching roles:', error);
        toast.error('Error fetching roles: ' + error.message);
    }
};

const submit = () => {
    form.post(route('account-creation.store'), {
        onSuccess: () => {
            toast.success('Account created successfully!');
            form.reset();
        },
        onError: (errors) => {
            toast.error('Error creating account.');
        }
    });
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
};

onMounted(() => {
    fetchRoles();
});
</script>

<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
}

.light-gray {
    background-color: $light-gray;
}

.text-green {
    color: $green;
}

.bg-green {
    background-color: $green;
}

.form-group {
    position: relative;
}

.form-label {
    font-weight: 500;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
    display: block;
}

select, input {
    width: 100%;
    transition: all 0.2s ease;
    
    &:focus {
        outline: none;
        border-color: $green;
        box-shadow: 0 0 0 2px rgba($green, 0.2);
    }
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.form-processing {
    animation: pulse 1.5s infinite;
}

@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>