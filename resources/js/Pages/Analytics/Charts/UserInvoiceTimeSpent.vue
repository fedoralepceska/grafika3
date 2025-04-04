<template>
    <div class="p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-6">{{ $t('workerAnalytics') }}</h2>

        <!-- Date Picker and Chart Container -->
        <div class="flex flex-row gap-3 border-2 rounded-lg">
            <!-- Custom Date Picker -->
            <div class="w-full max-w-md mx-auto">
                <CustomDatePicker
                    @date-selected="fetchWorkerAnalytics"
                    @reset-filters="resetData"
                    class="w-full"
                />
                <!-- Chart Type Selection Dropdown -->
                <div class="flex align-center justify-start p-6 items-center mb-4">
                    <label for="chartType" class="text-white font-semibold mr-4">{{ $t('selectChartType') }}:</label>
                    <select v-model="selectedChartType" id="chartType" class="px-auto py-2 border rounded-md text-black">
                        <option value="pie">{{ $t('pie') }}</option>
                        <option value="bar">{{ $t('bar') }}</option>
                        <option value="line">{{ $t('line') }}</option>
                    </select>
                </div>
            </div>

            <!-- Chart Container -->
            <div class="w-full bg-transparent">
                <div v-if="loading" class="flex justify-center items-center h-96">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-blue-500"></div>
                </div>
                <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">{{ $t('error') }}!</strong>
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
            <h3 class="text-xl font-semibold text-white mb-4">{{ $t('detailedBreakdown') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white text-black">
                    <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="py-2 px-4 text-left">{{ $t('workerId') }}</th>
                        <th class="py-2 px-4 text-left">{{ $t('totalTimeSpent') }}</th>
                        <th class="py-2 px-4 text-left">{{ $t('invoicesWorkedOn') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in tableData" :key="item.user_id" class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4">{{ item.user_id }}</td>
                        <td class="py-2 px-4">{{ item.formatted_time_spent }}</td>
                        <td class="py-2 px-4">{{ item.invoice_count }}</td>
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
    name: 'UserInvoiceTimeSpent',
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
            const options = {
                title: {
                    text: 'Worker Time Spent and Invoices Worked On',
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
                    formatter: (params) => {
                        if (selectedChartType.value === 'pie') {
                            // For pie chart, show the percentage
                            return `${params.seriesName} <br/>${params.name}: ${params.value} (${params.percent}%)`;
                        } else {
                            // For bar and line charts, no percentage
                            return `${params.seriesName} <br/>${params.name}: ${params.value}`;
                        }
                    },
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

            if (selectedChartType.value !== 'pie') {
                options.xAxis = {
                    type: 'category',
                    data: chartData.value.map(item => `Worker ${item.user_id}`), // Set category labels from the data
                    axisLabel: { color: '#fff' },
                };
                options.yAxis = {
                    type: 'value',
                    axisLabel: { color: '#fff' },
                };
                options.series = [{
                    name: 'Time Spent (seconds)',
                    type: selectedChartType.value, // Dynamic chart type (bar, line)
                    data: chartData.value.map(item => item.total_time_spent), // Data values
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
                options.series = [{
                    name: 'Time Spent (seconds)',
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
                    data: chartData.value.map(item => ({
                        value: item.total_time_spent,  // total_time_spent as value
                        name: `Worker ${item.user_id}`  // Worker ID as name
                    })), // Proper pie chart format
                }];
            }

            return options;
        });

        const tableData = computed(() => {
            return chartData.value;
        });

        const fetchWorkerAnalytics = async (selectedDate) => {
            loading.value = true;
            error.value = null;
            try {
                const response = await axios.get('/user-invoice-time-spent', {
                    params: { date: selectedDate },
                });
                chartData.value = Object.values(response.data).map(item => ({
                    user_id: item.user_id,
                    total_time_spent: item.total_time_spent,
                    invoice_count: item.invoice_count,
                    formatted_time_spent: item.formatted_time_spent
                }));
            } catch (err) {
                console.error('Error fetching data:', err);
                error.value = 'Failed to load data. Please try again later.';
            } finally {
                loading.value = false;
            }
        };

        const resetData = () => {
            chartData.value = [];
            fetchWorkerAnalytics();
        };

        onMounted(() => {
            fetchWorkerAnalytics();
        });

        return {
            chartOption,
            tableData,
            loading,
            error,
            fetchWorkerAnalytics,
            resetData,
            selectedChartType, // Expose chart type
        };
    },
};
</script>
