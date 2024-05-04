<template>
    <div class="status-box" @click="openLink">
        <div class="icon-container" :style="{border : borderColor}">
            <i :class="icon" :style="{ color: color }"/>
        </div>
        <div class="status-info">
            <span class="status-number text-white">{{ number }}</span>
            <span class="status-text text-white">{{ title }}</span>
        </div>
    </div>
</template>

<script>
export default {
    name: "StatusBox",
    props: {
        icon: String,
        title: String,
        number: Number,
        color: String,
        borderColor:{
            type:String,
            default: '2px solid #0073a9',
        },
        link: String
    },
    methods: {
        openLink() {
            // Open the new window with the specified route
            window.open(this.link, '_self');
        }
    }
}
</script>

<style scoped lang="scss">
.status-box {
    display: flex;
    align-items: center;
    background-color: $light-gray;
    border-radius: 2px;
    padding-bottom: 10px;
    justify-content: left;
    flex-grow: 1; /* Makes the boxes grow to fill the container */
    margin: 0 5px;
    height: 25vh;

}

.icon-container {
    padding:  24px 20px;
    /* Use a background-color that matches your icons' background for the circle */
    border-radius: 50%;
    margin-right: 10px;
    margin-left: 5px;
}
.status-number {
    font-size: 1.5em;
    font-weight: bold;
    display: block;
}

.status-text {
    display: block;
    font-size: 0.8em;
}

</style>
