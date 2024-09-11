<template>
    <div class="p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-6">Article Invoice Analytics</h2>



        <!-- Date Picker and Chart Container -->
        <div class="flex flex-row gap-3 border-2 rounded-lg">
            <!-- Custom Date Picker -->
            <div class="w-full max-w-md mx-auto ">
                <CustomDatePicker
                    @date-selected="fetchData"
                    class="w-full"
                />
                <!-- Chart Type Selection Dropdown -->
                <div class="flex align-center justify-start p-6 items-center mb-4">
                    <label for="chartType" class="text-white font-semibold mr-4">Select Chart Type:</label>
                    <select v-model="selectedChartType" id="chartType" class="px-auto py-2 border rounded-md text-black">
                        <option value="pie">Pie Chart</option>
                        <option value="bar">Bar Chart</option>
                        <option value="line">Line Chart</option>
                    </select>
                </div>
            </div>


            <!-- Chart Container -->
            <div class="w-full bg-transparent">
                <div v-if="loading" class="flex justify-center items-center h-96">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-blue-500"></div>
                </div>
                <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ error }}</span>
                </div>
                <v-chart
                    v-else
                    :option="chartOption"
                    class="w-full rounded-lg"
                    style="height: 400px;"
                    autoresize
                />
            </div>
        </div>

        <!-- Data Table -->
        <div class="mt-8">
            <h3 class="text-xl font-semibold text-white mb-4">Detailed Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="py-2 px-4 text-left">Article Name</th>
                        <th class="py-2 px-4 text-left">Order Count</th>
                        <th class="py-2 px-4 text-left">Percentage</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in tableData" :key="item.name" class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4">{{ item.name }}</td>
                        <td class="py-2 px-4">{{ item.value }}</td>
                        <td class="py-2 px-4">{{ item.percentage.toFixed(2) }}%</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>


<script>
import { ref, computed, onMounted } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart, BarChart, LineChart } from 'echarts/charts';
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components';
import VChart from 'vue-echarts';
import CustomDatePicker from "@/Components/inputs/CustomDatePicker.vue";
import axios from 'axios';

use([CanvasRenderer, PieChart, BarChart, LineChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent]);

export default {
    name: 'ArticleInvoiceChart',
    components: {
        VChart,
        CustomDatePicker,
    },
    setup() {
        const chartData = ref([]);
        const loading = ref(false);
        const error = ref(null);
        const selectedChartType = ref('pie'); // Track the selected chart type

        const chartOption = computed(() => {
            // Common chart options
            const options = {
                title: {
                    text: 'Order Distribution by Article',
                    left: 'center',
                    top: 20,
                    textStyle: {
                        color: '#fff',
                        fontWeight: 'bold',
                        fontSize: 20,
                    },
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)',
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    borderColor: '#fff',
                    borderWidth: 1,
                    textStyle: {
                        color: '#fff',
                    },
                },
                legend: {
                    orient: 'horizontal',
                    bottom: 10,
                    left: 'center',
                    textStyle: {
                        color: '#fff',
                        fontSize: 12,
                    },
                },
                color: ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'],
            };

            // If selected chart is not pie, add axes
            if (selectedChartType.value !== 'pie') {
                options.xAxis = {
                    type: 'category',
                    data: chartData.value.map(item => item.name), // Set category labels from the data
                    axisLabel: { color: '#fff' },
                };
                options.yAxis = {
                    type: 'value',
                    axisLabel: { color: '#fff' },
                };
                options.series = [{
                    name: 'Invoice Count',
                    type: selectedChartType.value, // Dynamic chart type (bar, line)
                    data: chartData.value.map(item => item.value), // Data values
                    itemStyle: {
                        borderRadius: 10,
                        borderColor: '#fff',
                        borderWidth: 2,
                    },
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)',
                        },
                    },
                }];
            } else {
                // If pie chart, no xAxis or yAxis needed
                options.series = [{
                    name: 'Invoice Count',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 10,
                        borderColor: '#fff',
                        borderWidth: 2,
                    },
                    label: {
                        show: false,
                        position: 'center',
                    },
                    emphasis: {
                        label: {
                            show: true,
                            fontSize: 18,
                            fontWeight: 'bold',
                        },
                    },
                    labelLine: {
                        show: false,
                    },
                    data: chartData.value, // Pie chart requires data in a different format
                }];
            }

            return options;
        });

        const tableData = computed(() => {
            const total = chartData.value.reduce((sum, item) => sum + item.value, 0);
            return chartData.value.map(item => ({
                ...item,
                percentage: (item.value / total) * 100,
            }));
        });

        const fetchData = async (selectedDate) => {
            loading.value = true;
            error.value = null;
            try {
                const response = await axios.get('/article-invoice-counts', {
                    params: { date: selectedDate },
                });
                chartData.value = response.data.map(item => ({
                    value: item.invoice_count,
                    name: item.article_name,
                }));
            } catch (err) {
                console.error('Error fetching data:', err);
                error.value = 'Failed to load data. Please try again later.';
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            fetchData();
        });

        return {
            chartOption,
            tableData,
            loading,
            error,
            fetchData,
            selectedChartType, // Expose chart type
        };
    },
};
</script>

