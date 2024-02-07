<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/inputs/Dropdown.vue';
import DropdownLink from '@/Components/inputs/DropdownLink.vue';
import NavLink from '@/Components/inputs/NavLink.vue';
import ResponsiveNavLink from '@/Components/inputs/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import Sidebar from "@/Components/Sidebar.vue";
const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="background" style="min-height: 100vh">
        <nav class="background border-b border-gray-700">
            <!-- Primary Navigation Menu -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo
                                    class="block h-9 w-auto fill-current text-gray-800"
                                />
                            </Link>
                        </div>

                        <!-- Place To Add Navigation Links -->
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <Button @click="openInProgress" class="p-4 text-white text-sm leading-4 font-medium">
                            <i class="fa-solid fa-spinner" style="color: white"></i>
                            &nbsp;&nbsp;In Progress
                        </Button>
                        <Button @click="openNewWindow" class="p-12 text-white text-sm leading-4 font-medium">
                            <i class="fa-solid fa-window-restore" style="color: white"></i>
                            &nbsp;&nbsp;New window
                        </Button>
                        <!-- Language selector -->
                        <LanguageSelector/>
                        <!-- Settings Dropdown -->
                        <div class="ml-3 relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button

                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-50 background  hover:text-gray-400 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 dark-gray focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                        >

                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div
                :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="sm:hidden"
            >
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        ALL THE LINKS FROM SIDEBAR
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-200">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>
        <!-- SIDEBAR START-->
        <div class="flex">
            <div >
                <SideMenu  class="md:block w-1/6"/>
            </div>
            <div class="width ">
                <slot/>
            </div>
        </div>
    </div>
</template>

<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import LanguageSelector from "@/Components/LanguageSelector.vue";
import SideMenu from "@/Components/SideMenu.vue";
export default {
    name: "MainLayout",
    components: {AuthenticatedLayout, LanguageSelector, SideMenu},
    data() {
        return {
            showSidebar: true
        }
    },
    methods: {
        openNewWindow() {
            console.log('test');
            var routeUrl = '/orders/create';

            // Open the new window with the specified route
            window.open(routeUrl, '_blank');
        },
        openInProgress() {
            var routeUrl = '/orders?status=In%20Progress';

            // Open the new window with the specified route
            window.open(routeUrl, '_self');
        }
    }
}

</script>

<style scoped lang="scss">

    .background {
        background-color: $background-color;
        height: max-content;
    }
    .dark-gray{
        background-color: $dark-gray;
    }

    .slide-fade-enter-active, .slide-fade-leave-active {
        transition: all 0.3s ease;
    }
    .slide-fade-enter, .slide-fade-leave-to /* .slide-fade-leave-active in <2.1.8 */ {
        transform: translateX(-100%);
        opacity: 0;
    }
    .width {
        width: 100%;
        margin-left: 70px;
    }
</style>
