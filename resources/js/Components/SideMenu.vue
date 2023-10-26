<template>
    <sidebar-menu :menu="menu" />
</template>

<script>

export default {
    data() {
        return {
            menu: [

                {
                    href: '/',
                    title: 'Dashboard',
                    icon: 'fa fa-user',
                },
                {
                    href: '',
                    title: 'Invoice',
                    icon: 'mdi-receipt-text',
                    child: [
                        {
                            href: '/invoices',
                            title: 'Create Invoice',
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
