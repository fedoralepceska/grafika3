<template>
    <div class="tab-content" v-show="title===selectedTitle">
        <slot/>
    </div>
</template>
<script>
import {inject} from "vue";
export default {
    props: {
        title: String
    },
    setup(){
        const selectedTitle = inject("selectedTitle")

        return {
            selectedTitle
        }
    }
}

</script>
