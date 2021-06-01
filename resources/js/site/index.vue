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
                <b-button variant="danger" size="sm" @click="removeSite(data.item.id)">x</b-button>
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
            this.$http.delete(`/api/site/${id}`).then(() => {
                this.$refs["site-table"].refresh();
            });
        }
    }
}
</script>

<style scoped>

</style>
