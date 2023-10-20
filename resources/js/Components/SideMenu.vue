<template>
    <div class="bg-gray-800 text-white h-screen w-64 px-4 py-6"
         :class="showSidebar ? 'streched' : 'shrinked'"
         @mouseover="showMenuText = true"
         @mouseleave="showMenuText = false"
    >
        <!-- Main Menu Items -->
        <div v-for="item in menuItems" :key="item.title" class="hover:bg-gray-700 rounded">
            <div class="flex items-center">
                <v-icon class="text-xl mr-4">{{ item.icon }}</v-icon>
                <div v-if="showSidebar">{{ item.title }}</div>
            </div>
        </div>

        <!-- Submenus -->
        <!-- Submenus -->
        <v-list-group
            v-for="submenu in submenus"
            :key="submenu.title"
            class="my-2"
            active-class="bg-gray-700"
            value="true"
        >
            <template v-slot:activator>
                <div class="hover-item hover:bg-gray-700 rounded flex items-center"
                     @mouseover="handleMouseOver"
                     @mouseleave="handleMouseLeave"
                >
                    <v-icon class="text-xl mr-4">{{ submenu.icon }}</v-icon>
                    <div v-if="showSidebar">{{ submenu.title }}</div>
                </div>
            </template>

        <!-- Wrap submenu items with v-list-item -->
            <div v-if="isHovered">
                <v-list-item v-for="item in submenu.items" :key="item.title" class="show hover:bg-gray-700 rounded">
                    <div class="flex items-center">
                        <div v-if="showSidebar">{{ item.title }}</div>
                    </div>
                </v-list-item>
            </div>
        </v-list-group>

    </div>
</template>

<script>
export default {
    name: "SideMenu",
    data() {
        return {
            showMenuText: false,
            isHovered: false,
            menuItems: [
                { title: "Home", icon: "mdi-home" },
                // ... Add more single items here
            ],
            submenus: [
                {
                    title: "Customer Management",
                    icon: "mdi-account-group",
                    items: [
                        { title: "Dashboard", icon: "mdi-view-dashboard" },
                        // ... Add more submenu items here
                    ],
                },
                // ... Add more submenus here
            ],
        };
    },
    props: {
        showSidebar: Boolean
    },
    methods: {
        handleMouseOver() {
            this.isHovered = true;
        },
        handleMouseLeave() {
            this.isHovered = false;
        }
    }
};
</script>

<style scoped lang="scss">
.shrinked {
    width: 60px;
}
.streched {
    width: 250px;
}
@media (max-width: 768px) {
    .hidden {
        display: block;
    }
}
</style>
