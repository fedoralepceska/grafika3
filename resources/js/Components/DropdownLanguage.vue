<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
            <span
                class="ml-2"
                :class="{
                    'transform rotate-180': open,
                }"
            >
                <!-- Add arrow icon here -->
                &#9660;
            </span>
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-show="open" class="absolute z-50 mt-2 rounded-md shadow-lg" :class="[widthClass, alignmentClasses]" @click="preventClose">
                <div class="rounded-md ring-1 ring-black ring-opacity-5 dark-gray" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    props: {
        align: {
            type: String,
            default: 'right',
        },
        width: {
            type: String,
            default: '48',
        },
        contentClasses: {
            type: String,
            default: 'py-1 bg-white',
        },
    },
    setup() {
        const open = ref(false);

        const widthClass = {
            '48': 'w-48',
        }[props.width.toString()];

        const alignmentClasses = {
            left: 'origin-top-left left-0',
            right: 'origin-top-right right-0',
            top: 'origin-top',
        }[align];

        const preventClose = (e) => {
            e.stopPropagation();
        };

        return {
            open,
            widthClass,
            alignmentClasses,
            preventClose,
        };
    },
};
</script>
