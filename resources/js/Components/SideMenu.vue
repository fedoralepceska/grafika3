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
                        // {
                        //     href: '/smallFormat/materials/create',
                        //     title: 'Add Material - Small',
                        // },
                        // {
                        //     href: '/materials-small-format',
                        //     title: 'View Material - Small',
                        // },
                        // {
                        //     href: '/largeFormat/materials/create',
                        //     title: 'Add Material Format - Large',
                        // },
                        // {
                        //     href: '/materials-large',
                        //     title: 'View Material Format - Large',
                        // },
                    ],
                },
                {
                  href: 'warehouse',
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
