<template>
    <div class="tabs">
        <ul class="tabs__header">
            <li v-for="tab in tabs"
                :key="tab.props.title"
                :class="{selected: tab.props.title === selectedTitle}"
                class="uppercase font-weight-bold"
                @click="selectedTitle = tab.props.title"
            >
                {{tab.props.title}}
                <v-icon>{{tab.props.icon}}</v-icon>
            </li>
        </ul>
        <slot/>
    </div>
</template>
<script>
import {ref, provide} from "vue";

export default {

    setup(props, {slots}){
        const tabs = ref(slots.default())
        // probably not needed
        const tabTitles = ref(slots.default().map((tab)=> tab.props.title))
        const selectedTitle = ref(tabTitles.value[0])

        provide("selectedTitle", selectedTitle)
        return{
            selectedTitle,
            tabTitles,
            tabs
        }
    }
}
</script>
<style scoped lang="scss">
.tabs{

}
.tabs__header{
    justify-content: center;
    margin-bottom: 10px;
    list-style: none;
    padding: 0;
    display: flex;
}

.tabs__header li {
    width: 90%;
    min-width: 260px;
    text-align: center;
    padding: 10px 20px;
    background-color: $gray;
    cursor: pointer;
    transition: 0.4s all ease-out;
    color: white;
}
.tabs__header li.selected{
    background-color: $light-gray;
}
</style>
