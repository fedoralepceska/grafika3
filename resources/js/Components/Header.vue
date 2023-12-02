<template>
    <div class="header pt-3 pb-4">
        <div class="left mr-3">
            <img :src="`/images/${icon}`" alt="UserLogo" class="image-icon" />
        </div>
        <div class="right">
            <h1 class="page-title">{{ $t(title) }}</h1>
            <h3 class="text-white"><span class="green">{{ $t(title) }}</span> / {{ $t(subtitle) }}</h3>
        </div>
    </div>
</template>

<script>
export default {
    name: "Header",
    props: {
        title: String,
        subtitle: String,
        icon: String
    }
}
</script>

<style scoped lang="scss">
.green{
    color: $green;
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.header {
    display: flex;
    flex-direction: row;
    align-items: center;
}
</style>
