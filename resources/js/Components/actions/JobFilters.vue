<template>
    <div class="filters">
        <div class="row">
            <div class="search-bar">
                <input type="text" v-model="searchQuery" placeholder="Search customers, orders & Job Titles" />
            </div>
            <div class="filter-group">
                <select v-model="materialFilter">
                    <option v-for="material in materials" :key="material" :value="material">
                        {{ $t(`materials.${material}`) }}
                    </option>
                </select>
                <select v-model="shippingMethodFilter">
                    <option>Shipping method filter</option>
                    <!-- Add more options as needed -->
                </select>
            </div>
            <div class="actions-group">
                <button class="go-btn">Go</button>
                <button>Find Multiple Orders</button>
                <button @click="clearSearch">Clear Search</button>
            </div>
        </div>
        <div class="row">
            <div class="buttons-group">
                <button
                    :class="{ 'selected': selectedButton === 'all' }"
                    @click="selectButton('all')"
                >All</button>
                <button
                    :class="{ 'selected': selectedButton === 'shippingToday' }"
                    @click="selectButton('shippingToday')"
                >Shipping Today</button>
                <button
                    :class="{ 'selected': selectedButton === 'shippingTomorrow' }"
                    @click="selectButton('shippingTomorrow')"
                >Shipping Tomorrow</button>
                <button
                    :class="{ 'selected': selectedButton === 'shipping2Days' }"
                    @click="selectButton('shipping2Days')"
                >Shipping In 2 days</button>
                <button
                    :class="{ 'selected': selectedButton === 'shipping3Days' }"
                    @click="selectButton('shipping3Days')"
                >Shipping In 3 Days</button>
            </div>
            <div class="buttons-group">
                <button
                    :class="{ 'selected': selectedButton === 'all' }"
                    @click="selectButton('all')"
                >All</button>
                <button
                    :class="{ 'selected': selectedButton === 'allUnassigned' }"
                    @click="selectButton('allUnassigned')"
                >All Unassigned</button>
            </div>
        </div>
        <div class="order-type">
            Order Type <select class="order-type" v-model="orderType">
                <option>-- All Orders --</option>
                <!-- Add more options as needed -->
            </select>
        </div>
    </div>
</template>

<script>
export default {
    name: 'JobFilters',
    data() {
        return {
            materials: this.generateMaterials(),
            searchQuery: '',
            materialFilter: '',
            shippingMethodFilter: '',
            orderType: '',
            selectedButton: ''
        }
    },
    methods: {
        generateMaterials() {
            const materials = [];
            for (let i = 1; i <= 28; i++) {
                materials.push(`Material ${i}`);
            }
            return materials;
        },
        clearSearch() {
            this.searchQuery = '';
            this.materialFilter = '';
            this.shippingMethodFilter = '';
            this.orderType = '';
        },
        selectButton(button) {
            this.selectedButton = button;
        }
    }

}
</script>

<style scoped lang="scss">
.filters {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px;
    background-color: $background-color;
}

.search-bar input {
    width: 250px;
    margin-right: 10px;
}

.filter-group,
.actions-group,
.buttons-group {
    display: flex;
    margin-right: 20px;
}

button {
    padding: 5px 10px;
    cursor: pointer;
    border: 1px solid $green;
    background-color: $light-gray;
    color: $white;
    font-weight: bold;
}

.go-btn {
    background-color: $blue;
    color: white;
    margin-left: 10px;
}

.row {
    display: flex;
    flex-direction: row;
}

.order-type {
    display: flex;
    align-items: center;
    justify-content: center;
    color: $white;
}

.selected {
    background-color: $green;
}
</style>
