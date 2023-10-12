<template>
    <button
        class="green inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest green-hover focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-50"
    >
        <slot />
    </button>
</template>

<style scoped lang="scss">
.green {
    background-color: $green;

    &-hover:hover {
        background-color: lighten($green, 5%);
    }
}
</style>
