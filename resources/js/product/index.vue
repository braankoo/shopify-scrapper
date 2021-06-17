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
            <template #cell(url)="data" class="text-center">
                <a :href="data.item.url" target="_blank">{{ data.item.url }}</a>
            </template>
            <template #cell(image)="data" class="text-center">
                <img :src="data.item.image" class="img-thumbnail img-fluid" style="max-height: 100px;" alt=""/>
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
                    key: 'site'
                },
                {
                    key: 'catalog',
                    sortable: true
                },
                {
                    key: 'product',
                    sortable: true,
                },
                {
                    key: 'image',
                    sortable: false
                },
                {
                    key: 'url',
                },
                {
                    key: 'type',
                    sortable: true
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
                    key: 'products.position',
                    sortable: true,
                    label: 'position'
                },
                {
                    key: 'quantity',
                    sortable: true
                },
                {
                    key: 'sales',
                    sortable: true
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
