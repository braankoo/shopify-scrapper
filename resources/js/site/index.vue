<template>
    <b-card header="Sites">
        <b-table api-url="/api/site"
                 id="site"
                 :busy.sync="isBusy"
                 :items="getSites"
                 :current-page="currentPage"
                 :per-page="perPage"
                 table-variant="light"
                 head-variant="light"
                 :striped="true"
                 :bordered="true"
                 :outlined="true"
                 ref="site-table"
        >
            <template #cell(id)="data" class="text-center">
                <b-button-group>
                    <b-button variant="outline-success" size="sm" @click="fetchData(data.item.id)">Fetch Data</b-button>
                    <b-button disabled size="sm"></b-button>
                    <b-button variant="outline-danger" size="sm" @click="removeSite(data.item.id)">x</b-button>
                </b-button-group>

            </template>
        </b-table>
        <template #footer>
            <b-pagination
                v-model="currentPage"
                :total-rows="totalRows"
                :per-page="perPage"
                aria-controls="site"
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
            perPage: 10,
            isBusy: false,
            fields: [
                {
                    key: 'id',
                    label: 'Actions'
                }
            ]
        }
    },
    methods: {
        async getSites(ctx) {
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

        },
        removeSite(id) {
            if (window.confirm('Are you sure?')) {
                this.$http.delete(`/api/site/${id}`).then(() => {
                    this.$refs["site-table"].refresh();
                });
            }
        },
        fetchData(id) {
            if (window.confirm('Are you sure?')) {
                this.$http.post(`/api/site/${id}/fetch`).then(() => {
                    alert('Successfuly initialized');
                });
            }
        }

    }
}
</script>

<style scoped>

</style>
