<template>
    <div class="flex flex-row justify-between">
        <CustomDatePicker @date-selected="fetchUserInvoiceCounts" @reset-filters="resetData" class="mt-16"/>
        <v-chart :option="chartOption" style="height: 400px;"></v-chart>
    </div>
</template>

<script>
import { ref } from 'vue';
import ECharts from 'vue-echarts';
import * as echarts from 'echarts/core'; // Import core ECharts functions
import { CanvasRenderer } from 'echarts/renderers'; // Import the necessary renderers
import { PieChart } from 'echarts/charts'; // Import the PieChart
import { TitleComponent, TooltipComponent, LegendComponent } from 'echarts/components'; // Import necessary components
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
    name: 'ArticleInvoiceChart',
    components: {
        'v-chart': ECharts,
        CustomDatePicker,
    },
    setup() {
        const chartOption = ref({
            title: {
                text: 'Invoice Count by Article',
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
                    name: 'Invoice Count',
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
                const response = await axios.get('/article-invoice-counts', {
                    params: { date: selectedDate },
                });

                const data = response.data;
                chartOption.value.series[0].data = data.map(item => ({
                    value: item.invoice_count,
                    name: item.article_name,
                }));
                chartOption.value.legend.data = data.map(item => item.article_name);
            } catch (error) {
                console.error('Error fetching user invoice counts:', error);
            }
        };

        const resetData = () => {
            chartOption.value = {
                title: {
                    text: 'Invoice Count by Article',
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
                        name: 'Invoice Count',
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

        return {
            chartOption,
            fetchUserInvoiceCounts,
            resetData,
        };
    },
};
</script>
