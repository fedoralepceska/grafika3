<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="User Management" subtitle="Manage Users" icon="UserLogo.png" link="user-management"/>
            <div class="dark-gray p-5 text-white">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">All Users</h2>
                    <div class="flex justify-end mb-4 space-x-4">
                        <CreateRoleDialog @role-created="fetchRoles" />
                        <Link :href="route('account-creation')" class="btn create-order">
                            Create New User <i class="fa fa-user-plus"></i>
                        </Link>
                    </div>
                    <table class="min-w-full">
                        <thead class="bg-gray-200 text-gray-700">
                            <tr>
                                <th class="px-2 py-2 text-left">Name</th>
                                <th class="px-2 py-2 text-center">Email</th>
                                <th class="px-2 py-2 text-center">Role</th>
                                <th class="px-2 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="border-b">
                                <td class="px-2 py-2">{{ user.name }}</td>
                                <td class="px-2 py-2 text-center">{{ user.email }}</td>
                                <td class="px-2 py-2 text-center">
                                    <select 
                                        v-model="user.role_id" 
                                        @change="updateUserRole(user)"
                                        class="rounded text-gray-700 px-4 py-2"
                                    >
                                        <option value="">No Role</option>
                                        <option 
                                            v-for="role in roles" 
                                            :key="role.id" 
                                            :value="role.id"
                                        >
                                            {{ role.name }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button 
                                            @click="openPasswordModal(user)"
                                            class="btn-change-password"
                                            title="Change Password"
                                        >
                                            <i class="fa fa-key"></i>
                                        </button>
                                        <button 
                                            @click="confirmDeleteUser(user)"
                                            :disabled="user.has_orders"
                                            :class="user.has_orders ? 'btn-delete-user-disabled' : 'btn-delete-user'"
                                            :title="user.has_orders ? 'Cannot delete user with orders/invoices' : 'Delete User'"
                                        >
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Password Change Modal -->
        <div v-if="showPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="modal-container">
                <h3 class="modal-title">Change Password for {{ selectedUser?.name }}</h3>
                <form @submit.prevent="changePassword">
                    <div class="modal-field">
                        <label class="modal-label">New Password</label>
                        <input
                            type="password"
                            v-model="passwordForm.password"
                            class="modal-input"
                            required
                            minlength="8"
                        />
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Confirm Password</label>
                        <input
                            type="password"
                            v-model="passwordForm.password_confirmation"
                            class="modal-input"
                            required
                            minlength="8"
                        />
                    </div>
                    <div class="modal-actions">
                        <button
                            type="button"
                            @click="closePasswordModal"
                            class="modal-btn modal-btn-cancel"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="modal-btn modal-btn-primary"
                        >
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="modal-container modal-delete">
                <h3 class="modal-title modal-title-danger">Delete User</h3>
                <p class="modal-message">
                    Are you sure you want to delete <strong>{{ selectedUser?.name }}</strong>? 
                    This action cannot be undone.
                </p>
                <div class="modal-actions">
                    <button
                        @click="closeDeleteModal"
                        class="modal-btn modal-btn-cancel"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteUser"
                        :disabled="deleteProcessing"
                        class="modal-btn modal-btn-danger"
                    >
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { useToast } from "vue-toastification";
import axios from 'axios';
import CreateRoleDialog from '@/Components/CreateRoleDialog.vue';
import { Link } from '@inertiajs/vue3';

const users = ref([]);
const roles = ref([]);
const toast = useToast();

// Modal states
const showPasswordModal = ref(false);
const showDeleteModal = ref(false);
const selectedUser = ref(null);
const deleteProcessing = ref(false);

// Password form
const passwordForm = ref({
    password: '',
    password_confirmation: '',
    processing: false
});

const fetchUsers = async () => {
    try {
        const response = await axios.get('/api/users');
        users.value = response.data;
    } catch (error) {
        console.error('Error fetching users:', error);
        toast.error('Error fetching users: ' + error.message);
    }
};

const fetchRoles = async () => {
    try {
        const response = await axios.get('/api/user-roles');
        roles.value = response.data;
    } catch (error) {
        console.error('Error fetching roles:', error);
        toast.error('Error fetching roles: ' + error.message);
    }
};

const updateUserRole = async (user) => {
    try {
        await axios.put(`/api/users/${user.id}/role`, {
            role_id: user.role_id
        });
        toast.success('User role updated successfully!');
    } catch (error) {
        console.error('Error updating user role:', error);
        toast.error('Error updating user role: ' + error.message);
        // Revert the selection in case of error
        fetchUsers();
    }
};

const resetUserRole = async (user) => {
    try {
        user.role_id = null;
        await axios.put(`/api/users/${user.id}/role`, {
            role_id: null
        });
        toast.success('User role reset successfully!');
    } catch (error) {
        console.error('Error resetting user role:', error);
        toast.error('Error resetting user role: ' + error.message);
        // Revert the selection in case of error
        fetchUsers();
    }
};

// Password change functions
const openPasswordModal = (user) => {
    selectedUser.value = user;
    passwordForm.value = {
        password: '',
        password_confirmation: '',
        processing: false
    };
    showPasswordModal.value = true;
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    selectedUser.value = null;
    passwordForm.value = {
        password: '',
        password_confirmation: '',
        processing: false
    };
};

const changePassword = async () => {
    if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
        toast.error('Passwords do not match!');
        return;
    }

    passwordForm.value.processing = true;
    
    try {
        await axios.put(`/api/users/${selectedUser.value.id}/password`, {
            password: passwordForm.value.password,
            password_confirmation: passwordForm.value.password_confirmation
        });
        
        toast.success('Password changed successfully!');
        closePasswordModal();
    } catch (error) {
        console.error('Error changing password:', error);
        toast.error('Error changing password: ' + (error.response?.data?.message || error.message));
    } finally {
        passwordForm.value.processing = false;
    }
};

// User deletion functions
const confirmDeleteUser = (user) => {
    // Check if user has orders first
    if (user.has_orders) {
        toast.error('Cannot delete user. User has associated orders/invoices.');
        return;
    }
    
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedUser.value = null;
    deleteProcessing.value = false;
};

const deleteUser = async () => {
    deleteProcessing.value = true;
    
    try {
        await axios.delete(`/api/users/${selectedUser.value.id}`);
        
        toast.success('User deleted successfully!');
        closeDeleteModal();
        fetchUsers(); // Refresh the user list
    } catch (error) {
        console.error('Error deleting user:', error);
        
        if (error.response?.data?.error === 'has_jobs') {
            toast.error('Cannot delete user. User has associated jobs/orders.');
        } else {
            toast.error('Error deleting user: ' + (error.response?.data?.message || error.message));
        }
    } finally {
        deleteProcessing.value = false;
    }
};

onMounted(() => {
    fetchUsers();
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

table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table td, table th {
    border-right: 1px solid #ddd;
    border-left: 1px solid #ddd;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.btn {
    padding: 8px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 3px;
    background-color: $green;
    color: white;
}

select {
    width: 200px;
    &:focus {
        outline: none;
        border-color: $blue;
    }
}

.btn-change-password {
    padding: 6px 10px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 3px;
    background-color: $blue;
    color: white;
    transition: background-color 0.2s;
    
    &:hover {
        background-color: darken($blue, 10%);
    }
    
    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-delete-user {
    padding: 6px 10px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 3px;
    background-color: #dc3545;
    color: white;
    transition: background-color 0.2s;
    
    &:hover {
        background-color: darken(#dc3545, 10%);
    }
    
    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.btn-delete-user-disabled {
    padding: 6px 10px;
    border: none;
    cursor: not-allowed;
    font-weight: bold;
    border-radius: 3px;
    background-color: #6c757d;
    color: white;
    opacity: 0.6;
}

// Modal styling to match system colors
.modal-container {
    background-color: $light-gray;
    border-radius: 8px;
    padding: 24px;
    width: 400px;
    max-width: 90vw;
    margin: 0 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.modal-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 16px;
    color: $white;
}

.modal-title-danger {
    color: #dc3545;
}

.modal-field {
    margin-bottom: 16px;
}

.modal-label {
    display: block;
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 8px;
    color: $white;
}

.modal-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    color: #333;
    transition: border-color 0.2s;
    
    &:focus {
        outline: none;
        border-color: $blue;
        box-shadow: 0 0 0 2px rgba($blue, 0.2);
    }
}

.modal-message {
    color: $white;
    margin-bottom: 24px;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.modal-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;
    
    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
}

.modal-btn-cancel {
    background-color: #6c757d;
    color: white;
    
    &:hover:not(:disabled) {
        background-color: darken(#6c757d, 10%);
    }
}

.modal-btn-primary {
    background-color: $blue;
    color: white;
    
    &:hover:not(:disabled) {
        background-color: darken($blue, 10%);
    }
}

.modal-btn-danger {
    background-color: #dc3545;
    color: white;
    
    &:hover:not(:disabled) {
        background-color: darken(#dc3545, 10%);
    }
}
</style> 