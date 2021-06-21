<template>
    <b-card header="Products" style="overflow: scroll;">
        <b-row>
            <b-col>
                <multiselect :options="filters.loaded.site.url"
                             v-model="filters.selected.site.url"
                             label="site"
                             track-by="id"
                             :multiple="true"
                             :searchable="true"
                             :internal-search="false"
                             :clear-on-select="false"
                             :close-on-select="false"
                             :options-limit="300"
                             :limit="3"
                             :max-height="600"
                             :show-no-results="false"
                             :hide-selected="true"
                             @search-change="find('site','url',$event)"
                             placeholder="Sites"/>
            </b-col>
            <b-col>
                <multiselect
                    :options="filters.loaded.catalog.title"
                    v-model="filters.selected.catalog.title"
                    label="title"
                    track-by="title"
                    :multiple="true"
                    :searchable="true"
                    :internal-search="false"
                    :clear-on-select="false"
                    :close-on-select="false"
                    :options-limit="300"
                    :limit="3"
                    :max-height="600"
                    :show-no-results="false"
                    :hide-selected="true"
                    @search-change="find('catalog','title',$event)"
                    placeholder="Catalog"/>
            </b-col>
            <b-col>
                <multiselect
                    :options="filters.loaded.product.title"
                    v-model="filters.selected.product.title"
                    label="title"
                    track-by="id"
                    :multiple="true"
                    :searchable="true"
                    :internal-search="false"
                    :clear-on-select="false"
                    :close-on-select="false"
                    :options-limit="300"
                    :limit="3"
                    :max-height="600"
                    :show-no-results="false"
                    :hide-selected="true"
                    @search-change="find('product','title',$event)"
                    placeholder="Product"/>
            </b-col>
            <b-col>
                <multiselect
                    :options="filters.loaded.product.type"
                    v-model="filters.selected.product.type"
                    label="type"
                    track-by="type"
                    :multiple="true"
                    :searchable="true"
                    :internal-search="false"
                    :clear-on-select="false"
                    :close-on-select="false"
                    :options-limit="300"
                    :limit="3"
                    :max-height="600"
                    :show-no-results="false"
                    :hide-selected="true"
                    @search-change="find('product','type',$event)"
                    placeholder="Type"/>
            </b-col>
        </b-row>
        <hr>

        <b-row>
            <b-col>
                <b-input type="number" v-model="filters.selected.position" placeholder="Position"/>
            </b-col>
            <b-col>
                <b-input type="number" v-model="filters.selected.quantity" placeholder="Quantity"/>
            </b-col>
            <b-col>
                <b-input type="number" v-model="filters.selected.sales" placeholder="Sales"/>
            </b-col>
        </b-row>
        <hr>
        <b-row>
            <b-col>
                <b-form-datepicker
                    v-model="filters.selected.created_at.start_date"
                    locale="en"
                    placeholder="Created At (start)"
                    :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"
                ></b-form-datepicker>
            </b-col>
            <b-col>
                <b-form-datepicker
                    v-model="filters.selected.created_at.end_date"
                    locale="en"
                    placeholder="Created At (end)"
                    :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"
                ></b-form-datepicker>
            </b-col>
        </b-row>
        <hr>
        <b-row>
            <b-col>
                <b-form-datepicker
                    v-model="filters.selected.published_at.start_date"
                    locale="en"
                    placeholder="Published At (start)"
                    :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"
                ></b-form-datepicker>
            </b-col>
            <b-col>
                <b-form-datepicker
                    v-model="filters.selected.published_at.end_date"
                    locale="en"
                    placeholder="Published At (end)"
                    :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"
                ></b-form-datepicker>
            </b-col>
        </b-row>
        <hr>
        <b-row>
            <b-col>
                <b-button @click="exportCSV" variant="success">CSV</b-button>
            </b-col>
        </b-row>
        <hr>
        <b-table api-url="/api/product/data"
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
                 :filter="filters.selected"
                 ref="products-table"
        >
            <template #cell(product)="data" class="text-center">
                <router-link :to="{ name: 'Product Historical', params: { product: data.item.product_id }}">
                    {{ data.item.product }}
                </router-link>
            </template>
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
import Multiselect from 'vue-multiselect';

const fileDownload = require('js-file-download');
export default {
    name: "index",
    components: {
        Multiselect
    },
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

            ],
            filters: {
                loaded: {
                    site: {
                        url: []
                    },
                    catalog: {
                        title: []
                    },
                    product: {
                        title: [],
                        type: []
                    },

                },
                selected: {
                    site: {
                        url: []
                    },
                    catalog: {
                        title: []
                    },
                    product: {
                        title: [],
                        type: []
                    },
                    created_at: {
                        start_date: '',
                        end_date: ''
                    },
                    published_at: {
                        start_date: '',
                        end_date: ''
                    },
                    quantity: '',
                    sales: '',
                    position: ''

                }
            }
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
                        filter: ctx.filter
                    }
                });
                this.totalRows = response.data.total;
                this.perPage = response.data.per_page;
                return response.data.data;
            } catch (error) {
                return []
            }

        },
        find(type, param, query) {
            console.log(type, query);
            this.$http.get(`api/${type}`, {
                params: {
                    [param]: query
                }
            }).then((response) => {
                console.log(response);
                this.filters.loaded[type][param] = response.data.data;
            });
        },
        exportCSV() {
            this.$http.get('/api/product/csv', {
                responseType: 'blob',
                params: {
                    filter: this.filters.selected
                }
            }).then((response) => {
                fileDownload(response.data, 'stats.csv');
            });
        }
    }
}
</script>

<style scoped>

</style>
