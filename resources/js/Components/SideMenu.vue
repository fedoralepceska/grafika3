<template>
    <sidebar-menu :menu="menu" :disable-hover="true" :collapsed="true"/>
</template>

<script>

export default {
    data() {
        return {
            menu: [

                {
                    href: '/dashboard',
                    title: 'Dashboard',
                    icon: 'fa fa-user',
                },
                {
                    href: '',
                    title: 'Invoice',
                    icon: 'fas fa-receipt',
                    child: [
                        {
                            href: '/invoices',
                            title: 'View all invoices',
                        },
                        {
                            href: '/invoices/create',
                            title: 'Create Invoice',
                        },
                    ],
                },
                {
                    href: '',
                    title: 'Materials',
                    icon: 'fa-solid fa-boxes-stacked',
                    child: [
                        {
                            href: '/smallFormat/materials/create',
                            title: 'Add Small Format Material',
                        },
                        {
                            href: '/SmallFormatMaterials',
                            title: 'View Small Format Material',
                        },
                    ],
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
            ],
        }
    },

}
</script>

<style >
@import "@/Sidebar/sidebar-custom.scss";
.v-sidebar-menu {

    border-right: 1px solid #77808b;
    background-color: #1a2732;
}

.v-sidebar-menu.vsm_expanded {
}
.v-sidebar-menu.vsm_collapsed {

}
.v-sidebar-menu.vsm_rtl {
}
.v-sidebar-menu .vsm--item {
}
.v-sidebar-menu .vsm--link_hover {

}
.v-sidebar-menu .vsm--link_active {
}
.v-sidebar-menu .vsm--link_open {

}
.v-sidebar-menu .vsm--link_mobile {
}
.v-sidebar-menu .vsm--link_level-[n] {

}
.v-sidebar-menu .vsm--link_disabled {
}
.v-sidebar-menu .vsm--title {

}
.v-sidebar-menu .vsm--icon {

}
.v-sidebar-menu .vsm--arrow {
}
.v-sidebar-menu .vsm--arrow_open {

}
.v-sidebar-menu .vsm--badge {
}
.v-sidebar-menu .vsm--badge_default {
}
.v-sidebar-menu .vsm--header {
}
.v-sidebar-menu .vsm--dropdown {
}
.v-sidebar-menu .vsm--mobile-bg {
}
.v-sidebar-menu .vsm--toggle-btn {

}
</style>
