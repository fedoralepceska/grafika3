<template>
    <sidebar-menu 
        :menu="menu" 
        :disable-hover="true" 
        :collapsed="true"
        class="v-sidebar-menu"
    />
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import '@/Sidebar/sidebar-custom.scss';

const page = usePage();
const menu = computed(() => page.props.sidebarMenu);
</script>

<style scoped>
@import "@/Sidebar/sidebar-custom.scss";

.v-sidebar-menu {
    border-right: 1px solid #77808b;
    background-color: #1a2732;
}
</style>
