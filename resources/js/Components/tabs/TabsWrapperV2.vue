<template>
    <div class="tabs">
        <ul class="tabs__header">
            <li v-for="tab in tabs"
                :key="tab.props.title"
                :class="{selected: tab.props.title === selectedTitle}"
                class="font-weight-bold"
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
        console.log('tw2', selectedTitle);

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
    margin: 15px;
    list-style: none;
    padding: 0;
    display: flex;
}

.tabs__header li {
    margin: 2px;
    min-width: 150px;
    max-width: 248px;
    text-align: center;
    padding: 3px 60px;
    background-color:$light-gray ;
    cursor: pointer;
    transition: 0.4s all ease-out;
    color: white;
    border: 1px $green solid;
    border-radius: 5px;
}
.tabs__header li.selected{
    background-color:$green ;
}
</style>
