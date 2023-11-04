// store.js
import { createStore } from 'vuex';

export default createStore({
    state: {
        shippingDetails: '',
        selectedJobs: []
    },
    mutations: {
        updateShippingDetails(state, details) {
            state.shippingDetails = details.shipping;
            state.selectedJobs = details.jobs;
        }
    }
});
