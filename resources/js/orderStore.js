// store.js
import { createStore } from 'vuex';

export default createStore({
    state: {
        shippingDetails: ''
    },
    mutations: {
        updateShippingDetails(state, details) {
            state.shippingDetails = details;
        }
    }
});
