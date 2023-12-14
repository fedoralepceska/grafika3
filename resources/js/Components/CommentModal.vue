<template>
    <div class="modal" v-if="showModal">
        <div class="modal-content">
            <div class="note pb-1">
                <div class="flex">
                <p>Read Note</p>
                    <button @click="closeModal" class="bg-white pr-2 pl-2 rounded bg" ><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="light p-5 mt-5 mb-16 rounded">
                <p>{{ comment }}</p>
            </div>
            <div class="flex pt-16">
            <button class="bt1" @click="closeModal"><i class="fa-solid fa-xmark"></i> Close</button>
            <button class="bt2" @click="acknowledge">Acknowledge <i class="fa-solid fa-check"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        showModal:Boolean,
        comment: String,
        closeModal: Function,
        acknowledge: Function,
    },
};
</script>

<style scoped lang="scss">
/* Styles for your modal */
.modal {
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}
.flex{
    display: flex;
    justify-content: space-between;
}
.bg{
    color: $background-color;
}
.light{
    background-color: $light-gray;
}
.note{
    border-bottom: 1px solid $light-gray;
}
.modal-content {
    background: $background-color;
    padding: 20px;
    border-radius: 8px;
    width: 80vh;
    height: 45vh;
}
.bt1{
    padding:7px 10px;
    cursor: pointer;
    color: white;
    background-color: $red;
    border-radius: 2px;
}
.bt2{
    padding:7px 10px;
    cursor: pointer;
    color: white;
    background-color: $green;
    border-radius: 2px;
}
</style>
