<template>
    <div id="main-container">
        <b-container :fluid="true">
            <b-row>
                <sidebar-menu :menu="menu" width="200px"/>

                <b-col cols="10" class="pt-1 offset-2">
                    <router-view></router-view>
                </b-col>
            </b-row>
        </b-container>
    </div>
</template>

<script>
import {SidebarMenu} from 'vue-sidebar-menu'

export default {
    name: "Container",
    components: {
        SidebarMenu
    },
    data() {
        return {
            menu: [
                {
                    header: true,
                    title: 'Navigation'
                },
                {
                    title: 'Sites',
                    child: [
                        {
                            href: '/site',
                            title: 'Show All'
                        },
                        {
                            href: '/site/create',
                            title: 'Add New'
                        }
                    ]
                },
                {
                    title: 'Catalog',
                    href: '/catalog'
                },
                {
                    title: 'Products',
                    href: '/product',
                }

            ]
        }
    },
}
</script>

<style scoped>
#main-container {
    font-family: 'Roboto', sans-serif;
    font-size: 11px !important;
    line-height: 12px !important;
}


</style>
