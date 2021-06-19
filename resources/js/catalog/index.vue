<template>
    <b-card>
        <b-row>
            <b-col>
                <b-input v-model="filter.title"></b-input>
            </b-col>
        </b-row>
        <hr>
        <b-table api-url="/api/catalog"
                 id="site"
                 :busy.sync="isBusy"
                 :items="getCatalogs"
                 :current-page="currentPage"
                 :per-page="perPage"
                 table-variant="light"
                 head-variant="light"
                 :striped="true"
                 :bordered="true"
                 :outlined="true"
                 ref="catalog-table"
                 :fields="fields"
                 :filter="filter.title"
        >
            <template #cell(status)="data" class="text-center">
                <b-form-checkbox
                    value="ENABLED"
                    unchecked-value="DISABLED"
                    checked="ENABLED"
                    name="check-button"
                    switch
                    @change="status(data.item.id, $event)"/>

            </template>
        </b-table>
        <b-pagination
            v-model="currentPage"
            :total-rows="totalRows"
            :per-page="perPage"
            aria-controls="catalog-table"
            size="sm"
        ></b-pagination>
    </b-card>

</template>

<script>
export default {
    name: "index",
    data() {
        return {
            filter: {
                title: ''
            },
            currentPage: 1,
            totalRows: 1,
            perPage: 10,
            isBusy: false,
            fields: [
                {
                    key: 'title'
                },
                {
                    key: 'status'
                }
            ]
        }
    },
    methods: {
        async getCatalogs(ctx) {
            try {

                const response = await this.$http.get(`${ctx.apiUrl}`, {
                    params: {
                        page: ctx.currentPage,
                        perPage: ctx.perPage,
                        sortBy: ctx.sortBy,
                        sortDesc: ctx.sortDesc,
                        title: ctx.filter
                    }
                });
                this.totalRows = response.data.total;
                this.perPage = response.data.per_page;
                return response.data.data;
            } catch (error) {
                return []
            }

        },
        status(id, status) {
            this.$http.patch(`/api/catalog/${id}`, {
                status: status
            }).then((response) => {
                console.log(response);
            })

        }
    }
}
</script>

<style scoped>

</style>
