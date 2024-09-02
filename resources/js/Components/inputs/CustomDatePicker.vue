<template>
    <div class="flex flex-col items-end space-y-2">
        <select v-model="selectedDay" class="text-black">
            <option value="" disabled>Select Day</option>
            <option v-for="day in dayOptions" :key="day" :value="day">{{ day }}</option>
        </select>

        <select v-model="selectedMonth" class="text-black">
            <option value="" disabled>Select Month</option>
            <option v-for="month in monthOptions" :key="month.value" :value="month.value">{{ month.text }}</option>
        </select>

        <select v-model="selectedYear" class="text-black">
            <option value="" disabled>Select Year</option>
            <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
        </select>
        <div class="flex flex-row justify-between w-full">
            <button @click="resetFilters" class="underline text-white">Reset</button>
            <SecondaryButton @click="submitDate">Submit</SecondaryButton>
        </div>
    </div>
</template>

<script>
import { ref, watch, computed } from 'vue';
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";

export default {
    name: 'CustomDatePicker',
    components: {SecondaryButton},
    emits: ['date-selected'],
    setup(props, { emit }) {
        const selectedDay = ref('');
        const selectedMonth = ref('');
        const selectedYear = ref('');

        const dayOptions = ref(Array.from({ length: 31 }, (_, i) => i + 1));

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

        const yearOptions = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i);

        const submitDate = () => {
            // Format day and month with leading zero if they are selected
            const day = selectedDay.value ? selectedDay.value.toString().padStart(2, '0') : '';
            const month = selectedMonth.value ? selectedMonth.value.toString().padStart(2, '0') : '';

            let date = '';

            if (day) {
                date = `${selectedYear.value}-${month}-${day}`;
            } else if (month) {
                date = `${selectedYear.value}-${month}`;
            } else {
                date = `${selectedYear.value}`;
            }

            emit('date-selected', date);
        };

        const resetFilters = () => {
            selectedDay.value = '';
            selectedMonth.value = '';
            selectedYear.value = '';
            this.$emit('reset-filters');
        };

        return {
            selectedDay,
            selectedMonth,
            selectedYear,
            dayOptions,
            monthOptions,
            yearOptions,
            submitDate,
            resetFilters,
        };
    },
};
</script>
<style scoped>
    select{
        width: 25vh;
        border-radius: 3px;
    }
</style>
