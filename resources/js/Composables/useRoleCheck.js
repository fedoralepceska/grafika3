import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';

export default function useRoleCheck() {
    const page = usePage();
    const roles = ref([]);
    
    const user = computed(() => page.props.auth.user);
    
    const isAuthenticated = computed(() => !!user.value);
    
    const roleId = computed(() => user.value?.role_id || null);
    
    const roleName = computed(() => {
        if (!roleId.value) return null;
        const role = roles.value.find(r => r.id === roleId.value);
        return role ? role.name : null;
    });
    
    const hasRole = (name) => {
        if (!roleId.value) return false;
        const role = roles.value.find(r => r.name === name);
        return role && roleId.value === role.id;
    };
    
    const hasRoleId = (id) => {
        return user.value?.role_id === id;
    };
    
    const isAdmin = computed(() => hasRole('Admin'));
    const isWorker = computed(() => hasRole('Worker'));
    const isAccountant = computed(() => hasRole('Accountant'));
    
    const fetchRoles = async () => {
        try {
            const response = await axios.get('/api/user-roles');
            roles.value = response.data;
        } catch (err) {
            console.error('Error fetching roles:', err);
        }
    };
    
    onMounted(() => {
        fetchRoles();
    });
    
    return {
        user,
        isAuthenticated,
        roleId,
        roleName,
        hasRole,
        hasRoleId,
        isAdmin,
        isWorker,
        isAccountant
    };
} 