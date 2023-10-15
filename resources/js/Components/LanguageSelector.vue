<template>
    <div class="language-selector">
        <div @click="toggleDropdown" class="language-button inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-50 background  hover:text-gray-400 focus:outline-none transition ease-in-out duration-150">
            <img :src="getFlagImage(currentLocale)" alt="Language Flag" class="flag-icon" />
            <span>{{ getLanguageName(currentLocale) }}</span>
            <span v-if="dropdownOpen" class="arrow-icon">
                <svg
                    class="ml-2 -mr-0.5 h-4 w-4"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </span>
            <span v-else class="arrow-icon">

            </span>
        </div>
        <div v-if="dropdownOpen" class="language-dropdown font-medium text-base text-gray-200">
            <div v-for="locale in supportedLocales" :key="locale" @click="changeLocale(locale)" class="language-option hover:text-gray-400">
                <img :src="getFlagImage(locale)" alt="Language Flag" class="flag-icon" />
                <span>{{ getLanguageName(locale) }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import {getCurrentLocale, setCurrentLocale} from "@/locale.js";
import i18n from "@/i18n.js";

export default {
    data() {
        return {
            supportedLocales: ["en", "mk"], // Add your supported locales here
            currentLocale: getCurrentLocale(), // Set the initial locale
            dropdownOpen: false,
        };
    },
    methods: {
        toggleDropdown() {
            this.dropdownOpen = !this.dropdownOpen;
        },
        changeLocale(locale) {
            setCurrentLocale(locale);
            i18n.global.locale = locale; // Set the current locale
            this.currentLocale = locale;
            this.dropdownOpen = false;
        },
        getLanguageName(locale) {
            return locale === "en" ? "English" : "Macedonian"; // Replace with your language names
        },
        getFlagImage(locale) {
            return `/images/${locale}.png`; // Adjust the image paths accordingly
        },
    },
};
</script>

<style scoped lang="scss">
.language-selector {
    position: relative;
    width: 150px;
}

.language-button {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.flag-icon {
    width: 24px;
    height: 24px;
    margin-right: 8px;
}

.arrow-icon {
    margin-left: 4px;
}

.language-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    background-color: $dark-gray;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 1;
}

.language-option {
    display: flex;
    align-items: center;
    padding: 8px;
    cursor: pointer;
}
</style>
