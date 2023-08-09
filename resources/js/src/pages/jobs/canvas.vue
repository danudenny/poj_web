<template>
    <div class="container-fluid">
        <Breadcrumbs main="Unit Jobs Structure"/>

        <div class="container-fluid">
            <div class="email-wrap bookmark-wrap">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-absolute">
                            <div class="card-header bg-primary">
                                <h5>Unit Jobs Structure</h5>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-outline-secondary" @click="this.$router.push('/management/struktur-jabatan')">
                                    <i class="fa fa-rotate-left"></i> Back
                                </button>
                                <div ref="orgChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import OrgChart from "@balkangraph/orgchart.js";
import {useToast} from "vue-toastification";
import axios from "axios";
export default {
    components: {
        OrgChart,
    },
    data() {
        return {
            charts: null,
            chartDatas: [],
        };
    },
    async mounted() {
        await this.chartData();
        this.loadOrgCharts();
    },
    methods: {
        async chartData() {
            await this.$axios.get(`/api/v1/admin/unit-job/chart-view?relation_id=${this.$route.params.id}`)
                .then(response => {
                    this.chartDatas = response.data.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        loadOrgCharts() {
            this.charts = new OrgChart(this.$refs.orgChart, {
                template: "ana",
                assistantSeparation: 170,
                toolbar: {
                    fullScreen: true,
                    zoom: true,
                    fit: true,
                    expandAll: true
                },
                align: OrgChart.ORIENTATION,
                enableDragDrop: true,
                nodeBinding: {
                    field_0: "name",
                    field_1: "title"
                },
                nodes: this.chartDatas,
            })
            this.charts.on('drop', async function (sender, draggedNodeId, droppedNodeId) {
                let draggedNode = sender.getNode(draggedNodeId);
                let droppedNode = sender.getNode(droppedNodeId);

                try {
                    await axios.post(`/api/v1/admin/unit-job/assign`, {
                        unit_has_job_id: draggedNode.id,
                        parent_unit_has_job_id: droppedNode.id
                    }).then(() => {
                        useToast().success("Success to create data");
                    }).catch(error => {
                        if(error.response.data.message instanceof Object) {
                            for (const key in error.response.data.message) {
                                useToast().error(error.response.data.message[key][0]);
                            }
                        } else {
                            useToast().error(error.response.data.message );
                        }
                    });
                } catch (error) {
                    console.log(error);
                }
            });
        }
    }
};
</script>
