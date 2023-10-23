<template>
    <div class="tabs">
        <ul class="tabs__header">
            <li v-for="title in tabTitles"
                :key="title"
                :class="{selected: title == selectedTitle}"
                @click="selectedTitle = title"

            >
                {{title}}
            </li>
        </ul>
        <slot/>
    </div>
</template>
<script>
import {ref, provide} from "vue";

export default {

    setup(props, {slots}){
    const tabTitles = ref(slots.default().map((tab)=> tab.props.title))
    const selectedTitle = ref(tabTitles.value[0])

        provide("selectedTitle", selectedTitle)
        return{
            selectedTitle,
            tabTitles,
        }
    }
}
</script>
<style scoped lang="scss">
.tabs{

}
.tabs__header{
    margin-bottom: 10px;
    list-style: none;
    padding: 0;
    display: flex;
}

.tabs__header li {
    width: 250px;
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
