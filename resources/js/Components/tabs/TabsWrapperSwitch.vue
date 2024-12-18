<template>
    <div class="switch-tabs">
        <div class="switch-container">
            <div class="switch-track"
                 :class="{ 'switch-right': selectedTitle === tabTitles[1] }">
                <button v-for="(tab, index) in tabs"
                        :key="tab.props.title"
                        :class="{ 'active': tab.props.title === selectedTitle }"
                        @click="selectedTitle = tab.props.title"
                        class="switch-button">
                    {{ tab.props.title }}
                </button>
                <div class="switch-slider"></div>
            </div>
        </div>
        <div class="switch-content">
            <slot/>
        </div>
    </div>
</template>

<script>
import { ref, provide } from "vue";

export default {
    setup(props, { slots }) {
        const tabs = slots.default()
        const tabTitles = tabs.map((tab) => tab.props.title)
        const selectedTitle = ref(tabTitles[0])

        provide("selectedTitle", selectedTitle)
        return {
            selectedTitle,
            tabTitles,
            tabs
        }
    }
}
</script>

<style scoped lang="scss">
.switch-tabs {
    width: 100%;
    padding: 0.5rem;
}

.switch-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.switch-track {
    position: relative;
    background-color: $dark-gray;
    border-radius: 30px;
    padding: 4px;
    display: flex;
    width: 300px;
    height: 40px;
}

.switch-button {
    flex: 1;
    border: none;
    background: none;
    color: $light-gray;
    font-weight: 500;
    cursor: pointer;
    position: relative;
    z-index: 2;
    transition: color 0.3s ease;

    &.active {
        color: $white;
    }
}

.switch-slider {
    position: absolute;
    left: 4px;
    top: 4px;
    width: calc(50% - 4px);
    height: calc(100% - 8px);
    background-color: $green;
    border-radius: 25px;
    transition: transform 0.3s ease;
}

.switch-track.switch-right .switch-slider {
    transform: translateX(100%);
}

.switch-content {
    margin-top: 1rem;
}
</style>
