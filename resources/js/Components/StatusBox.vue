<template>
    <div class="status-box">
        <div class="icon-container">
            <i :class="icon" :style="{ color: color }"/>
        </div>
        <div class="status-info">
            <span class="status-number">{{ number }}</span>
            <span class="status-text">{{ title }}</span>
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
        color: String
    }
}
</script>

<style scoped lang="scss">
.status-box {
    display: flex;
    align-items: center;
    background-color: $light-gray;
    border-radius: 2px;
    padding: 10px;
    justify-content: space-between;
    flex-grow: 1; /* Makes the boxes grow to fill the container */
    margin: 0 10px;
}

.icon-container {
    padding: 10px;
    /* Use a background-color that matches your icons' background for the circle */
    border-radius: 50%;
    margin-right: 10px;
}

.status-info {
    text-align: center;
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
