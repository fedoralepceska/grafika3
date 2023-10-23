<template>
    <div class="packing-actions">
        <button class="btn undo">Undo</button>
        <button class="btn reprint"><span class="mdi mdi-refresh"></span> Reprint</button>
        <button class="btn lock-order">Lock Order<span class="mdi mdi-lock"></span></button>
        <button class="btn add-steps">Add Steps +</button>
        <button class="btn force-process"><span class="mdi mdi-play-fast-forward"></span>Force Process
            <input type="checkbox">
        </button>
        <div class="spacer"></div>
        <button class="btn edit-order">Edit Order <span class="mdi mdi-square-edit-outline"></span></button>
        <button class="btn done">Done</button>
        <button class="btn reset-packing"><span class="mdi mdi-gift-outline"></span> Reset packing</button>
        <button class="btn pack-all"><span class="mdi mdi-gift-outline"></span> Pack All in Custom Packs</button>
        <button class="btn done-packing">Done Packing <span class="mdi mdi-thumb-up-outline"></span></button>
    </div>
</template>

<script>
export default {
    name: 'PackingActions'
}
</script>

<style scoped lang="scss">
.packing-actions {
    display: flex;
    gap: 8px;
    background-color: $light-gray;
    padding: 10px;
}

.btn {
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}

.undo {
    background-color: $red;
    color: white;
}

.reprint {
    background-color: $red;
    color: white;
}

.lock-order {
    background-color: $blue;
    color: white;
}

.add-steps {
    background-color: $blue;
    color: white;
}

.force-process {
    background-color: $orange;
    color: white;
}

.edit-order {
    background-color: $white;
    color: $dark-gray;
}

.done {
    background-color: $white;
    color: $dark-gray;
}

.reset-packing {
    background-color: $blue;
    color: white;
}

.pack-all {
    background-color: $blue;
    color: white;
}

.done-packing {
    background-color: $green;
    color: white;
}

.spacer {
    flex-grow: 1;
}
</style>
