<template>
    <div class="fc-search">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <input
            :value="modelValue"
            type="text"
            class="fc-input text-black"
            :placeholder="placeholder"
            autocomplete="off"
            @input="$emit('update:modelValue', $event.target.value)"
            @keyup.enter="$emit('submit')"
        />
    </div>
</template>

<script>
export default {
    name: 'FinanceCompactSearch',
    props: {
        modelValue: { type: String, default: '' },
        label: { type: String, default: '' },
        placeholder: { type: String, default: '' },
    },
    emits: ['update:modelValue', 'submit'],
};
</script>

<style scoped lang="scss">
.fc-search {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.fc-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.7);
}

.fc-input {
    width: 100%;
    min-height: 34px;
    border-radius: 8px;
    padding: 0 10px;
    font-size: 13px;
}

</style>
