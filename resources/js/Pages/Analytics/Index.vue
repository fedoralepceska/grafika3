<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between">
                <Header title="Analytics" subtitle="Dashboard" icon="analytics.png" link="analytics"/>
            </div>
            <div class="grid-container">
                <div v-for="item in analyticsItems" :key="item.title" class="grid-item bg" @click="navigateToAnalytics(item.link)">
                    <div class="analytics">
                        <div class="analyticsTitle text-white">
                            {{ item.title }} {{ $t('analytics') }}
                        </div>
                    </div>
                    <div class="status">
                        <svg class="line-chart" viewBox="0 0 100 50" preserveAspectRatio="none">
                            <path :d="generatePath()" fill="none" stroke="#408a0b" stroke-width="2">
                                <animate
                                    attributeName="stroke-dashoffset"
                                    from="200"
                                    to="0"
                                    dur="2s"
                                    fill="freeze"
                                />
                            </path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import { ref, onMounted } from 'vue';
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";

export default {
    components: {
        MainLayout,
        Header,
    },
    setup() {
        const analyticsItems = ref([
            { title: 'Workers', link: '/analytics/workers' },
            { title: 'Orders', link: '/analytics/orders' },
            { title: 'Clients', link: '/analytics/clients' },
            { title: 'Articles', link: '/analytics/articles' },
        ]);

        const generatePath = () => {
            const points = [];
            for (let i = 0; i < 10; i++) {
                points.push([i * 10, Math.random() * 40 + 5]);
            }
            return 'M' + points.map(point => point.join(',')).join(' L');
        };

        return {
            analyticsItems,
            generatePath,
        };
    },
    methods: {
        navigateToAnalytics(analyticsType) {
            this.$inertia.visit(analyticsType);
        }
    }
}
</script>

<style scoped>
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 25px;
    margin: 16px auto;
    background-color: #2c3e50;
}

.grid-item {
    background-color: #34495e;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.grid-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.analyticsTitle {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.status {
    margin-top: 20px;
}

.line-chart {
    width: 100%;
    height: 50px;
}

.line-chart path {
    stroke-dasharray: 200;
    stroke-dashoffset: 200;
}

@keyframes drawLine {
    to {
        stroke-dashoffset: 0;
    }
}
</style>
