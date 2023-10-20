<template>
    <div
        class="side dark-gray text-white h-screen  px-4 py-6"
        :class="{ 'streched': isSidebarStreched, 'shrinked': !isSidebarStreched }"
        @click="isSidebarStreched = !isSidebarStreched"
    >
        <!-- Main Menu Items -->
        <div
            v-for="(item, index) in menuItems"
            :key="item.title"
            class="hover:bg-gray-800 rounded"

        >
            <div class="flex items-center pb-2 pt-2">
                <v-icon class="text-xl mr-4">{{ item.icon }}</v-icon>
                <div  @click="clickHandler(index)" v-if="isSidebarStreched">{{ item.title }}</div>
            </div>

            <!-- Display submenus when a menu item is clicked -->
            <div v-if="item === activeItem && item.submenu" class="submenu">
                <v-list-item
                    v-for="(submenuItem, submenuIndex) in item.submenu"
                    :key="submenuIndex"
                    class="show hover:bg-gray-100 rounded"
                    @click="submenuClickHandler(submenuItem)"
                >
                    <div class="flex items-center">
                        <div v-if="isSidebarStreched ">{{ submenuItem.title }}</div>
                    </div>
                </v-list-item>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "SideMenu",
    data() {
        return {
            showMenuText: true,
            isSidebarStreched: false,
            menuItems: [
                {
                    title: "Home",
                    icon: "mdi-home",
                    clickable: true,
                },
                {
                    title: "Invoice",
                    icon: "mdi-receipt-text",
                    clickable: true,
                    submenu: [
                        { title: "Create Invoice", icon: "mdi-receipt" },
                        { title: "All Invoices", icon: "mdi-account" },
                        { title: "Completed Invoices", icon: "mdi-worker" },
                    ],
                },
                {
                    title: "Production",
                    icon: "mdi-factory",
                    clickable: true,
                    submenu: [
                        { title: "View all", icon: "mdi-worker" },
                        { title: "Sector 1", icon: "mdi-account" },
                        { title: "Sector 2", icon: "mdi-account" },
                        { title: "Sector 3", icon: "mdi-account" },
                        { title: "Sector 4", icon: "mdi-account" },
                    ],
                },
                {
                    title: "Client",
                    icon: "mdi-account-supervisor",
                    clickable: true,
                    submenu: [
                        { title: "Add a client", icon: "mdi-worker" },
                        { title: "Create an account", icon: "mdi-account" },
                    ],
                },
                {
                    title: "Materials",
                    icon: "mdi-palette-swatch",
                    clickable: true,
                    submenu: [
                        { title: "Add material", icon: "mdi-worker" },
                        { title: "View materials", icon: "mdi-account" },
                    ],
                },
                {
                    title: "Catalog",
                    icon: "mdi-notebook-outline",
                    clickable: true,
                    submenu: [
                        { title: "View catalog", icon: "mdi-worker" },
                        { title: "Add new item", icon: "mdi-account" },
                    ],
                },

                {
                    title: "Analytics",
                    icon: "mdi-google-analytics",
                    clickable: true,
                    submenu: [
                        { title: "User analytics", icon: "mdi-worker" },
                        { title: "Client analytics", icon: "mdi-account" },
                    ],
                },
                {
                    title: "Finance",
                    icon: "mdi-currency-usd",
                    clickable: true,
                    submenu: [
                        { title: "Invoiced", icon: "mdi-worker" },
                        { title: "Uninvoiced", icon: "mdi-account" },
                    ],
                },


                // ... Add more single items here
            ],
            activeItem: null, // Track the active menu item
        };
    },
    methods: {
        clickHandler(index) {
            const menuItem = this.menuItems[index];
            if (menuItem.clickable) {
                // Toggle the submenu when a clickable menu item is clicked
                if (this.activeItem === menuItem) {
                    this.activeItem = null;
                }
                else {
                    this.activeItem = menuItem;
                }
            }
            // Toggle sidebar stretching when any menu item is clicked
            this.isSidebarStreched = !this.isSidebarStreched;
        },
        submenuClickHandler(submenuItem) {
            // Handle submenu item click as needed (e.g., navigate to a route)
        },
    },
};
</script>
<style scoped lang="scss">
.shrinked {
    width: 60px;
}
.streched {
    width: 250px;
}
.dark-gray{
    background-color: $dark-gray;
}
.light-gray{
    background-color: $light-gray;
}
</style>

