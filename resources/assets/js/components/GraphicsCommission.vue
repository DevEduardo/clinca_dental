<template>
    <section class="container edit-service">
        <div class="row">
            <div class="col-xs-12">
                <h1>
                    <i class="glyphicon glyphicon-stats" v-if="! loading"></i>
                    <img src="/img/loading.gif" v-if="loading">
                    Reporte de Comision a doctores / servicios
                </h1>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <section>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="">Desde</label>
                                        <datepicker
                                            name = "start"
                                            id = "start"
                                            language="es"
                                            input-class = "form-control"
                                            format = "MM/dd/yyyy"
                                            @input="changeStart($event)"
                                            v-model="initStart"
                                            ></datepicker>
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="">Hasta</label>
                                        <datepicker
                                                name = "end"
                                                id = "end"
                                                language="es"
                                                input-class = "form-control"
                                                format = "MM/dd/yyyy"
                                                @input="changeEnd($event)"
                                                v-model="initEnd"
                                                ></datepicker>
                                    </div>
                                </div>

                                <div class="col-xs-12">
                                    <div class="form-group">
                                        <button
                                                class="btn btn-primary"
                                                @click="search()"
                                                v-if="!loading"
                                                >
                                            <i class="glyphicon glyphicon-search"></i>
                                            Buscar
                                        </button>
                                        <img src="/img/loading.gif" v-if="loading">
                                    </div>
                                </div>

                            </div>
                        </section>
                    </div>
                </div>
            </div>
            <div class="col-xs-8">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <section>
                            <div class="row">
                                <div class="col-xs-12">
                                    <line-chart-simple :data="datacollection"></line-chart-simple>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

    </section>
</template>

<script>
    import Datepicker from 'vuejs-datepicker';
    import LineChart from './LineChartSimple';

    export default {
        components: {
            Datepicker,
            LineChart
        },
        data: function () {
            return {
              loading: false,
              initStart: new Date(),
              initEnd: new Date(),
              datacollection: [],
              data: {
                  start: '',
                  end: '',
              },
            }
        },
        mounted: function () {
            const date = new Date();
            const day = date.getDate() >= 10 ? date.getDate() : '0' + date.getDate();
            const month = (date.getMonth() + 1) >= 10 ? (date.getMonth() + 1) : '0' + (date.getMonth() + 1);
            const year = date.getFullYear();

            this.data.start = year + '-' + month + '-' + day;
            this.data.end = year + '-' + month + '-' + day;
            this.search();
        },
        methods: {
            changeStart: function (date) {
                let day = (date.getDate() < 10) ? '0' + date.getDate() : date.getDate();
                let month = ((date.getMonth() + 1) < 10) ? '0' + (date.getMonth() + 1) : (date.getMonth() + 1);
                let year = date.getFullYear();

                this.data.start = year + '-' + month + '-' + day;
            },

            changeEnd: function (date) {
                let day = (date.getDate() < 10) ? '0' + date.getDate() : date.getDate();
                let month = ((date.getMonth() + 1) < 10) ? '0' + (date.getMonth() + 1) : (date.getMonth() + 1);
                let year = date.getFullYear();

                this.data.end = year + '-' + month + '-' + day;
            },

            search: function () {
                this.loading = true;
                let payments = 0;
                let commission = 0;
                let expenses = 0;
                this.datacollection = [];
                axios.get(
                    `/admin/data/grafica/comicion_servicios/${this.data.start}/${this.data.end}`
                )
                .then((res) => {
                    this.loading = false;
                    this.datacollection.push({
                        totalPayments: res.data.totalPayments,
                        totalExpenses: res.data.totalExpenses,
                        totalCommission: res.data.totalCommission
                    });
                })
                .catch((err) => {
                    
                    console.log(err);
                    this.loading = false;
                    this.data.payments = [];
                })
            },

            dateFormat: function (date) {
                let format = date.split(' ');
                format = format[0].split('-');

                return format[1] + '/' + format[2] + '/' + format[0];
            },
        }
    }
</script>