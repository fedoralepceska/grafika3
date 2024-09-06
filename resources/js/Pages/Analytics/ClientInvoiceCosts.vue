<template>
    <div class="flex flex-row justify-between">
        <CustomDatePicker @date-selected="fetchUserInvoiceCounts" @reset-filters="resetData" class="mt-16"/>
        <v-chart :option="chartOption" style="height: 400px;"></v-chart>
    </div>
</template>

<script>
import {onBeforeMount, ref} from 'vue';
import ECharts from 'vue-echarts';
import * as echarts from 'echarts/core'; // Import core ECharts functions
import {CanvasRenderer} from 'echarts/renderers'; // Import the necessary renderers
import {PieChart} from 'echarts/charts'; // Import the PieChart
import {LegendComponent, TitleComponent, TooltipComponent} from 'echarts/components'; // Import necessary components
import CustomDatePicker from "@/Components/inputs/CustomDatePicker.vue";

// Register the components
echarts.use([
    CanvasRenderer,
    PieChart,
    TitleComponent,
    TooltipComponent,
    LegendComponent,
]);

export default {
    name: 'ClientInvoiceCosts',
    components: {
        'v-chart': ECharts,
        CustomDatePicker,
    },
    setup() {
        const chartOption = ref({
            title: {
                text: 'Invoice Costs (ден.) by Client',
                left: 'center',
            },
            tooltip: {
                trigger: 'item',
            },
            legend: {
                orient: 'vertical',
                left: 'left',
            },
            series: [
                {
                    name: 'Invoice Costs (ден.)',
                    type: 'pie',
                    radius: '50%',
                    data: [],
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)',
                        },
                    },
                },
            ],
        });

        const fetchUserInvoiceCounts = async (selectedDate) => {
            try {
                const response = await axios.get('/client-invoice-costs-counts', {
                    params: { date: selectedDate },
                });

                const data = response.data;
                chartOption.value.series[0].data = Object.values(data).map(item => ({
                    value: item.total_cost,
                    name: item.client_name,
                }));

                chartOption.value.legend.data = data.map(item => item.client_name);
            } catch (error) {
                console.error('Error fetching user invoice counts:', error);
            }
        };

        const resetData = () => {
            chartOption.value = {
                title: {
                    text: 'Invoice Costs (ден.) by Client',
                    left: 'center',
                },
                tooltip: {
                    trigger: 'item',
                },
                legend: {
                    orient: 'vertical',
                    left: 'left',
                },
                series: [
                    {
                        name: 'Invoice Costs (ден.)',
                        type: 'pie',
                        radius: '50%',
                        data: [],
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)',
                            },
                        },
                    },
                ],
            };
        };

        onBeforeMount(() => {
            fetchUserInvoiceCounts();
        });

        return {
            chartOption,
            fetchUserInvoiceCounts,
            resetData,
        };
    },
};
</script>
