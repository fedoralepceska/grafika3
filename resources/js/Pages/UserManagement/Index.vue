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
                            </tr>
                        </tbody>
                    </table>
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
</style> 