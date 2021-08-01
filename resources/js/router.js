import VueRouter from "vue-router";
import index from './historical/index';
//
import create from "./site/create";
import SiteIndex from "./site/index";
//
import CatalogIndex from './catalog/index';

import ProductIndex from './product/index';
import Container from "./Container";

const router = new VueRouter(
    {
        linkActiveClass: 'open active',
        scrollBehavior: () => ({y: 0}),
        mode: 'history',
        routes: [
            {
                path: '/',
                component: Container,
                children: [
                    {
                        path: '/site',
                        component: SiteIndex
                    },
                    {
                        path: '/site/create',
                        component: create
                    },
                    {
                        path: '/product',
                        component: ProductIndex
                    },
                    {
                        path: 'product/:siteId/:product/historical',
                        component: index,
                        name: "Product Historical"
                    },
                    {
                        path: '/catalog',
                        component: CatalogIndex
                    },

                ]
            }
        ]
    }
);

export default router;
