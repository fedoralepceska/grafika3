import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        currentLocale: localStorage.getItem('locale') || 'en', // Default to English
    },
    mutations: {
        setLocale(state, locale) {
            state.currentLocale = locale;
            localStorage.setItem('locale', locale); // Store the locale in localStorage
        },
    },
});
