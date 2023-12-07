import { createRouter, createWebHistory } from 'vue-router';
import ActionPage from './Components/ActionPage.vue';
import Dashboard from "@/Pages/Production/Dashboard.vue"; // Import your components

// Define your routes
const routes = [
    {
        path: '/actions/:actionId',
        component: ActionPage,
        props: true // This allows the route parameters to be passed in as props
    },
    {
        path: '/production',
        component: Dashboard
    }
];

// Create the router instance
const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
});

export default router;
