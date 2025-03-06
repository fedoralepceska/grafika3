<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="userRoles" subtitle="allRoles" icon="UserLogo.png" link="user-roles"/>
            <div class="dark-gray p-5 text-white">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">All Roles</h2>
                    
                    <div class="flex justify-end mb-4">
                        <CreateRoleDialog @role-created="fetchRoles" />
                    </div>

                    <table class="min-w-full">
                        <thead class="bg-gray-200 text-gray-700">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="role in roles" :key="role.id" class="border-b">
                                <td class="px-6 py-4">{{ role.name }}</td>
                                <td class="px-6 py-4">
                            
                                    <button @click="confirmDelete(role)" class="py-2 px-4 rounded delete-btn">
                                        Delete
                                    </button>
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
import CreateRoleDialog from '@/Components/CreateRoleDialog.vue';
import { useToast } from "vue-toastification";
import axios from 'axios';

const roles = ref([]);
const toast = useToast();

const fetchRoles = async () => {
    try {
        const response = await axios.get('/api/user-roles');
        roles.value = response.data;
    } catch (error) {
        console.error('Error fetching roles:', error);
        toast.error('Error fetching roles: ' + error.message);
    }
};

const confirmDelete = async (role) => {
   
        try {
            await axios.delete(`/api/user-roles/${role.id}`);
            toast.success('Role deleted successfully!');
            fetchRoles();
        } catch (error) {
            console.error('Error deleting role:', error);
            toast.error('Error deleting role: ' + error.message);
        }
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

.delete-btn {
    
    background-color: $red;
    color: white;
}

.cancel-btn {
    background-color: $gray;
    color: white;
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
    padding: 10px;
    text-align: center;
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
</style> 