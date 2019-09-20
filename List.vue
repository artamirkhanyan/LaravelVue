<template>
    <div class="row">
        <div class="col-md-12"><h4 class="title">{{ $t('Companies List') }}</h4></div>
        <div class="col-md-12 card">
            <div class="card-body">
                <div class="row">
                    <div class="col text-right">
                        <router-link class="btn btn-success btn-wd" :to="{ name: 'CompaniesCreate' }">
                            {{ $t('Add new company') }}
                        </router-link>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <el-select class="select-default" v-model="pagination.perPage" placeholder="Per page">
                            <el-option
                                class="select-default"
                                v-for="item in pagination.perPageOptions"
                                :key="item"
                                :label="item"
                                :value="item"
                            >
                            </el-option>
                        </el-select>
                    </div>
                    <div class="col-sm-6">
                        <div class="pull-right">
                            <fg-input
                                class="input-sm"
                                v-bind:placeholder="$t('Search')"
                                v-model="searchQuery"
                                addon-right-icon="nc-icon nc-zoom-split"
                            >
                            </fg-input>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 mt-2">
                        <el-table class="table-striped" :data="queriedData" border style="width: 100%">
                            <el-table-column
                                v-for="column in tableColumns"
                                :key="column.label"
                                :min-width="column.minWidth"
                                :prop="column.prop"
                                :label="$t(column.label)"
                            >
                            </el-table-column>
                            <el-table-column :min-width="220" :label="$t('Address')">
                                <template slot-scope="props">
                                    {{ props.row.address_1 }}, {{ props.row.address_2 }}
                                </template>
                            </el-table-column>
                            <el-table-column
                                :min-width="120"
                                fixed="right"
                                class-name="td-actions text-center"
                                :label="$t('Actions')"
                            >
                                <template slot-scope="props">
                                    <router-link
                                        class="btn btn-icon btn-success btn-sm"
                                        :to="{ name: 'CompaniesEdit', params: { id: props.row.id } }"
                                    >
                                        <i class="fa fa-edit"></i>
                                    </router-link>
                                    <p-button
                                        type="danger"
                                        size="sm"
                                        icon
                                        @click="handleDelete(props.$index, props.row)"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </p-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-sm-6 pagination-info">
                        <p class="category">{{ $t('Showing') }} {{ from + 1 }} {{ $t('to') }} {{ to }} {{ $t('of') }} {{ total }} {{ $t('entries') }}</p>
                    </div>
                    <div class="col-sm-6">
                        <p-pagination
                            class="pull-right"
                            v-model="pagination.currentPage"
                            :per-page="pagination.perPage"
                            :total="pagination.total"
                        >
                        </p-pagination>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Vue from 'vue';
import swal from 'sweetalert2';
import { mapState } from 'vuex';
import { Table, TableColumn, Select, Option } from 'element-ui';
import PPagination from 'src/components/UIComponents/Pagination.vue';

Vue.use(Table);
Vue.use(TableColumn);
Vue.use(Select);
Vue.use(Option);

export default {
    components: {
        PPagination,
    },
    data() {
        return {
            pagination: {
                perPage: 25,
                perPageOptions: [5, 10, 25, 50],
                currentPage: 1,
                total: 0,
            },
            tableColumns: [
                {
                    prop: 'company_name',
                    label: 'Name',
                    minWidth: 200,
                },
                {
                    prop: 'company_type',
                    label: 'Type',
                    minWidth: 150,
                },
            ],
            searchQuery: '',
            propsToSearch: ['company_name', 'company_type'],
        };
    },
    computed: {
        ...mapState({
            tableData: state => state.companies.list,
        }),
        pagedData() {
            return this.tableData.slice(this.from, this.to);
        },
        queriedData() {
            if (!this.searchQuery) {
                this.pagination.total = this.tableData.length;
                return this.pagedData;
            }
            let result = this.tableData.filter(row => {
                let isIncluded = false;
                for (let key of this.propsToSearch) {
                    let rowValue = row[key].toString();
                    if (rowValue.includes && rowValue.toLowerCase().includes(this.searchQuery.toLowerCase())) {
                        isIncluded = true;
                    }
                }
                return isIncluded;
            });
            this.pagination.total = result.length;
            return result.slice(this.from, this.to);
        },
        to() {
            let highBound = this.from + this.pagination.perPage;
            if (this.total < highBound) {
                highBound = this.total;
            }
            return highBound;
        },
        from() {
            return this.pagination.perPage * (this.pagination.currentPage - 1);
        },
        total() {
            this.pagination.total = this.tableData.length;
            return this.tableData.length;
        },
    },
    methods: {
        fetchCompaniesList() {
            this.$store.dispatch('companies/fetch');
        },
        handleDelete(index, company) {
            swal({
                title: window.vueApp.$t('Are you sure?'),
                text: window.vueApp.$t('You want to delete this company'),
                type: 'warning',
                showCancelButton: true,
                confirmButtonClass: 'btn btn-success btn-fill',
                cancelButtonClass: 'btn btn-danger btn-fill',
                confirmButtonText: window.vueApp.$t('Yes, delete it!'),
                buttonsStyling: false,
            })
                .then(() => {
                    this.$store
                        .dispatch('companies/delete', company)
                        .then(() => {
                            this.$notify({
                                message: window.vueApp.$t('Company has been deleted successfully.'),
                                type: 'success',
                            });
                        })
                        .catch(() => {
                            this.$notify({
                                message: window.vueApp.$t('Company could not be deleted at this time. Please refresh and try again.'),
                                type: 'danger',
                            });
                        });
                })
                .catch(() => {});
        },
    },
    created() {
        this.fetchCompaniesList();
    },
};
</script>
