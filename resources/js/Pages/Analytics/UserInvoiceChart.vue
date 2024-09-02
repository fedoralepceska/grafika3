<template>
    <div class="flex flex-row justify-between">
        <CustomDatePicker @date-selected="fetchUserInvoiceCounts"  @reset-filters="resetData" class="mt-16"/>
        <v-chart :option="chartOption" style="height: 400px;"></v-chart>
    </div>
</template>

<script>
import { ref } from 'vue';
import ECharts from 'vue-echarts';
import * as echarts from 'echarts/core'; // Import core ECharts functions
import { CanvasRenderer } from 'echarts/renderers'; // Import the necessary renderers
import { BarChart } from 'echarts/charts'; // Import the BarChart
import { TitleComponent, TooltipComponent, GridComponent } from 'echarts/components'; // Import necessary components
import CustomDatePicker from "@/Components/inputs/CustomDatePicker.vue";

// Register the components
echarts.use([
    CanvasRenderer,
    BarChart,
    TitleComponent,
    TooltipComponent,
    GridComponent,
]);

export default {
    name: 'UserInvoiceChart',
    components: {
        'v-chart': ECharts,
        CustomDatePicker,
    },
    setup() {
        const chartOption = ref({
            xAxis: {
                type: 'category',
                data: [],
            },
            yAxis: {
                type: 'value',
            },
            series: [
                {
                    data: [],
                    type: 'bar',
                },
            ],
        });

        const fetchUserInvoiceCounts = async (selectedDate) => {
            try {
                const response = await axios.get('/user-invoice-counts', {
                    params: { date: selectedDate },
                });

                const data = response.data;
                chartOption.value.xAxis.data = data.map(item => item.user_name);
                chartOption.value.series[0].data = data.map(item => item.invoice_count);
            } catch (error) {
                console.error('Error fetching user invoice counts:', error);
            }
        };

        const resetData = () => {
            this.chartOption = {
                xAxis: {
                    type: 'category',
                    data: [],
                },
                yAxis: {
                    type: 'value',
                },
                series: [
                    {
                        data: [],
                        type: 'bar',
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
