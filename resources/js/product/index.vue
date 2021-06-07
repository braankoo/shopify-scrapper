<template>
    <b-card header="Products" style="overflow: scroll;">
        <b-table api-url="/api/product"
                 id="products"
                 :busy.sync="isBusy"
                 :items="getProducts"
                 :current-page="currentPage"
                 :fields="fields"
                 :per-page="perPage"
                 table-variant="light"
                 head-variant="light"
                 :striped="true"
                 :bordered="true"
                 :outlined="true"
                 ref="products-table"
        >
            <template #cell(variant_id)="data" class="text-center">
                <router-link :to="{name:'Variant Historical', params: {variant: data.item.variant_id}}">
                    {{ data.item.variant_id }}
                </router-link>
            </template>
            <template #cell(url)="data" class="text-center">
                <a :href="data.item.url" target="_blank">{{ data.item.url }}</a>
            </template>
            <template #cell(image_1)="data" class="text-center">
                <img :src="data.item.image_1" class="img-thumbnail img-fluid" alt=""/>
            </template>
            <template #cell(image_2)="data" class="text-center">
                <img :src="data.item.image_2" class="img-thumbnail img-fluid" alt=""/>
            </template>
            <template #cell(image_3)="data" class="text-center">
                <img :src="data.item.image_3" class="img-thumbnail img-fluid" alt=""/>
            </template>

        </b-table>
        <template #footer>
            <b-pagination
                v-model="currentPage"
                :total-rows="totalRows"
                :per-page="perPage"
                aria-controls="products"
                size="sm"
            ></b-pagination>
        </template>


    </b-card>
</template>

<script>
export default {
    name: "index",
    data() {
        return {
            currentPage: 1,
            totalRows: 1,
            perPage: 20,
            isBusy: false,
            fields: [
                {
                    key: 'title',
                    sortable: true,
                    label: 'Product'
                },
                {
                    key: 'name',
                    sortable: true,
                    label: 'Catalog'
                },
                {
                    key: 'variant_id',
                    sortable: true,
                    label: 'Variant'
                },
                {
                    key: 'position',
                    sortable: true,
                    label: 'Position'
                },
                {
                    key: 'price',
                },
                {
                    key: 'type',
                    sortable: true
                },
                {
                    key: 'url'
                },
                {
                    key: 'sku'
                },
                {
                    key: 'tags'
                },
                {
                    key: 'created_at',
                    sortable: true
                },
                {
                    key: 'published_at',
                    sortable: true
                },
                {
                    key: 'image_1'
                },
                {
                    key: 'image_2'
                },
                {
                    key: 'image_3'
                }


            ]
        }
    },
    methods: {
        async getProducts(ctx) {
            try {

                const response = await this.$http.get(`${ctx.apiUrl}`, {
                    params: {
                        page: ctx.currentPage,
                        perPage: ctx.perPage,
                        sortBy: ctx.sortBy,
                        sortDesc: ctx.sortDesc,
                    }
                });
                this.totalRows = response.data.total;
                this.perPage = response.data.per_page;
                return response.data.data;
            } catch (error) {
                return []
            }

        }
    }
}
</script>

<style scoped>

</style>
