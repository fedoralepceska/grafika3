<template>
    <sidebar-menu :menu="menu" :disable-hover="true" :collapsed="true"/>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import useRoleCheck from '@/Composables/useRoleCheck';

const { isAdmin, isWorker, isAccountant, hasRole } = useRoleCheck();

// Define menu as a computed property so it reacts to role changes
const menu = computed(() => [
    {
        href: '/dashboard',
        title: 'Dashboard',
        icon: 'fa fa-user',
    },
    {
        href: '',
        title: 'Order',
        icon: 'fas fa-receipt',
        child: [
            {
                href: '/orders',
                title: 'View all orders',
            },
            {
                href: '/orders/create',
                title: 'Create Order',
            },
        ],
    },
    {
        href: '',
        title: 'Materials',
        icon: 'fa-solid fa-layer-group',
        child: [
            {
                href: '/materials/small',
                title: 'Small Materials',
            },
            {
                href: '/materials/large',
                title: 'Large Materials',
            },
        ],
    },
    {
        href: '/warehouse',
        title: 'Warehouse',
        icon: 'fa-solid fa-warehouse',
    },
    {
        href: '',
        title: 'Clients',
        icon: 'fa-solid fa-users',
        child: [
            {
                href: '/clients',
                title: 'View all clients',
            },
            {
                href: '/clients/create',
                title: 'Add new client',
            },
        ],
    },
    {
        href: '',
        title: 'Production',
        icon: 'fa-solid fa-chart-pie',
        child: [
            {
                href: '/production',
                title: 'Dashboard',
            },
        ],
    },
    {
        href: '',
        title: 'Machines',
        icon: 'fa-solid fa-print',
        child: [
            {
                href: '/machines',
                title: 'Dashboard',
            },
        ],
    },
    {
        href: '/refinements',
        title: 'Refinements',
        icon: 'fa-solid fa-screwdriver-wrench',
    },
    {
        href: '',
        title: 'Articles',
        icon: 'fa-regular fa-newspaper',
        child: [
            {
                href: '/articles',
                title: 'View all articles',
            },
            {
                href: '/articles/create',
                title: 'Add new article',
            },
        ],
    },
    {
        href: '',
        title: 'Receipt',
        icon: 'fa-solid fa-file-invoice',
        child: [
            {
                href: '/receipt',
                title: 'View all receipts',
            },
            {
                href: '/receipt/create',
                title: 'Add new receipt',
            },
        ],
    },
    {
        href: '',
        title: 'Finances',
        icon: 'fa-solid fa-file-invoice-dollar',
        child: [
            {
                href: '/allInvoices',
                title: 'Invoiced Orders',
            },
            {
                href: '/notInvoiced',
                title: 'Uninvoiced Orders',
            },
            {
                href: '/incomingInvoice',
                title: 'Incoming Invoice',
            },
            {
                href: '/statements',
                title: 'Bank Statements',
            },
            {
                href: '/cardStatements',
                title: 'Client Card Statements',
            },
        ],
    },
    {
        href: '',
        title: 'Catalog',
        icon: 'fa-solid fa-book-open',
        child: [
            {
                href: '/catalog',
                title: 'View all catalog items',
            },
            {
                href: '/catalog/create',
                title: 'Add new catalog item',
            },
            {
                href: '/quantity-prices',
                title: 'Prices per quantity',
            },
            {
                href: '/client-prices',
                title: 'Prices per client',
            },
        ],
    },
    {
        href: '',
        title: 'Offers',
        icon: 'fa-solid fa-file-invoice',
        child: [
            {
                href: '/offers',
                title: 'View all offers'
            },
            {
                href: '/offer/create',
                title: 'Add new offer'
            },
        ],
    },
    {
        href: '/analytics',
        title: 'Analytics',
        icon: 'fa-solid fa-chart-pie',
        child: [
            {
                href: '/analytics-orders',
                title: 'Order - User',
            },
        ],
    },
    
    ...( isAdmin.value ? [{
        href: '',
        title: 'User Management',
        icon: 'fa-solid fa-user-gear',
        child: [
            {
                href: '/user-management',
                title: 'User Management',
            },
            {
                href: '/user-roles',
                title: 'User Roles',
            },
            {
                href: '/account-creation',
                title: 'Account Creation',
            },
        ],
    }] : []),
]);
</script>

<style>
@import "@/Sidebar/sidebar-custom.scss";
.v-sidebar-menu {
    border-right: 1px solid #77808b;
    background-color: #1a2732;
}

</style>
