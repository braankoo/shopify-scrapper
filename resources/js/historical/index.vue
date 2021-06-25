<template>
    <div>
        <b-card header="Historical">
            <b-row class="mb-2">
                <b-col cols="6" class="offset-6">
                    <b-row>
                        <b-col>
                            <b-form-datepicker
                                v-model="filters.date.start_date"
                                label-no-date-selected="Start Date"
                                :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"/>
                        </b-col>
                        <b-col>
                            <b-form-datepicker
                                v-model="filters.date.end_date"
                                label-no-date-selected="End Date"
                                :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"/>
                        </b-col>
                    </b-row>
                </b-col>
            </b-row>
            <b-table :api-url="`/api/product/${$route.params.product}`"
                     id="variants"
                     :busy.sync="isBusy"
                     :items="getHistorical"
                     :current-page="currentPage"
                     :per-page="perPage"
                     table-variant="light"
                     head-variant="light"
                     :filter="filters"
                     :striped="true"
                     :bordered="true"
                     :outlined="true"
                     ref="variants-table"
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
                    aria-controls="variants"
                    size="sm"
                ></b-pagination>
            </template>
        </b-card>
    </div>
</template>
<script>
import moment from "moment";

export default {
    name: "index",

    data() {
        return {

            currentPage: 1,
            totalRows: 1,
            perPage: 20,
            isBusy: false,
            filters: {
                date: {
                    start_date: moment().subtract(5, 'days').format('YYYY-MM-DD'),
                    end_date: moment().format('YYYY-MM-DD')
                },
            }
        }
    },

    methods: {
        async getHistorical(ctx) {
            try {

                const response = await this.$http.get(`${ctx.apiUrl}`, {
                    params: {
                        page: ctx.currentPage,
                        perPage: ctx.perPage,
                        filter: ctx.filter
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
