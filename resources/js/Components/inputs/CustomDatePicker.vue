<template>
    <div class="dark-gray rounded-lg p-6 max-w-md w-full ">
        <h2 class="text-2xl font-semibold mb-4 text-white">Select Date Range</h2>
        <div class="space-y-4">
            <div class="flex space-x-4">
                <div class="flex-1">
                    <label for="year" class="block text-sm text-white font-medium text-gray-700 mb-1">Year</label>
                    <select
                        id="year"
                        v-model="selectedYear"
                        class="block text-black w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="" disabled>Select Year</option>
                        <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="month" class="block text-white text-sm font-medium mb-1">Month</label>
                    <select
                        id="month"
                        v-model="selectedMonth"
                        class="block text-black w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="" disabled>Select Month</option>
                        <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                            {{ month.text }}
                        </option>
                    </select>
                </div>
            </div>
            <div>
                <label for="day" class="block text-sm font-medium text-white mb-1">Day</label>
                <select
                    id="day"
                    v-model="selectedDay"
                    :disabled="!selectedMonth || !selectedYear"
                    class="block text-black w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 disabled:bg-gray-100 disabled:text-gray-500"
                >
                    <option value="" disabled>Select Day</option>
                    <option v-for="day in availableDays" :key="day" :value="day">{{ day }}</option>
                </select>
            </div>
        </div>
        <div class="mt-6 flex justify-between items-center">
            <button
                @click="resetFilters"
                class="text-sm reset rounded-md text-white bg-[#9e2c30] px-4 py-2 transition-colors duration-200"
            >
                Reset
            </button>
            <button
                @click="submitDate"
                :disabled="!isValid"
                class="px-4 py-2 apply text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
                Apply
            </button>
        </div>
    </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
    name: 'CustomDatePicker',
    emits: ['date-selected', 'reset-filters'],
    setup(props, { emit }) {
        const selectedDay = ref('');
        const selectedMonth = ref('');
        const selectedYear = ref('');

        const monthOptions = [
            { text: 'January', value: '01' },
            { text: 'February', value: '02' },
            { text: 'March', value: '03' },
            { text: 'April', value: '04' },
            { text: 'May', value: '05' },
            { text: 'June', value: '06' },
            { text: 'July', value: '07' },
            { text: 'August', value: '08' },
            { text: 'September', value: '09' },
            { text: 'October', value: '10' },
            { text: 'November', value: '11' },
            { text: 'December', value: '12' },
        ];

        const yearOptions = computed(() => {
            const currentYear = new Date().getFullYear();
            return Array.from({ length: 10 }, (_, i) => currentYear - i);
        });

        const availableDays = computed(() => {
            if (!selectedMonth.value || !selectedYear.value) return [];
            const daysInMonth = new Date(selectedYear.value, selectedMonth.value, 0).getDate();
            return Array.from({ length: daysInMonth }, (_, i) => (i + 1).toString().padStart(2, '0'));
        });

        const isValid = computed(() => {
            return selectedYear.value && (selectedMonth.value ? (selectedDay.value ? true : false) : true);
        });

        const submitDate = () => {
            if (!isValid.value) return;

            const date = [
                selectedYear.value,
                selectedMonth.value,
                selectedDay.value
            ].filter(Boolean).join('-');

            emit('date-selected', date);
        };

        const resetFilters = () => {
            selectedDay.value = '';
            selectedMonth.value = '';
            selectedYear.value = '';
            emit('reset-filters');
        };

        return {
            selectedDay,
            selectedMonth,
            selectedYear,
            monthOptions,
            yearOptions,
            availableDays,
            isValid,
            submitDate,
            resetFilters,
        };
    },
};
</script>
<style scoped lang="scss">
.apply{
    background-color: $green;
}
.apply:hover{
    background-color: darkgreen;
}
.reset{
    background-color: $red;
}
.reset:hover{
    background-color: darkred;
}
</style>
