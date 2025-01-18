<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Offer" subtitle="Create New Offer" icon="List.png" link="offers" />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <h2 class="sub-title">Offer Creation</h2>

                    <form @submit.prevent="submit" class="space-y-6 w-full rounded-lg">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <!-- Choose offer -->
                                <div>
                                    <label class="text-white">Offers</label>
                                    <VueMultiselect
                                        :searchable="true"
                                        v-model="form.selectedOffers"
                                        :options="offers"
                                        :multiple="true"
                                        label="name"
                                        placeholder="Select an offer/s"
                                    >
                                        <template v-slot:option="{ option }">
                                            {{option.name}}
                                        </template>
                                    </VueMultiselect>
                                </div>
                            </div>
                            <!-- Choose client -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">Clients</label>
                                    <VueMultiselect
                                        :searchable="true"
                                        v-model="form.selectedClient"
                                        :options="clients"
                                        :multiple="true"
                                        label="name"
                                        placeholder="Select a client"
                                    >
                                        <template v-slot:option="{ option }">
                                            {{option.name}}
                                        </template>
                                    </VueMultiselect>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="submit" class="btn btn-primary">
                                Create Offer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
    import MainLayout from '@/Layouts/MainLayout.vue';
    import Header from '@/Components/Header.vue';
    import { useToast } from 'vue-toastification';
    import VueMultiselect from 'vue-multiselect'
    import axios from 'axios';

    export default {
        name: 'Create',
        components: {
            MainLayout,
            Header,
            VueMultiselect
        },
        data() {
            return {
                form: {
                    selectedOffers: [],
                    selectedClient: []
                },
                clients: [],
                offers: [],
            };
        },
        async mounted() {
            await this.fetchClients();
            await this.fetchOffers();
        },
        methods: {
            async fetchClients() {
                try {
                    let response = await axios.get('/clients');
                    this.clients = response?.data?.data;
                } catch (error) {
                    console.error("Failed to fetch clients:", error);
                }
            },
            async fetchOffers() {
                try {
                    let response = await axios.get('/offers');
                    this.offers = response?.data;
                } catch (error) {
                    console.error("Failed to fetch clients:", error);
                }
            },
            async submit() {
                try {
                    // Prepare the payload for the request
                    const payload = this.form.selectedOffers.map(offer => {
                        return this.form.selectedClient.map(client => ({
                            offer_id: offer.id,
                            client_id: client.id,
                            is_accepted: null,
                            description: ''
                        }));
                    }).flat();

                    // Send the data to the server
                    await axios.post('/offer-client', { associations: payload });

                    // Show success toast notification
                    useToast().success('Offers successfully assigned to clients!');
                    this.$inertia.visit('/offer-client');

                    // Optional: Reset form
                    this.form.selectedOffers = [];
                    this.form.selectedClient = [];
                } catch (error) {
                    console.error("Failed to submit offer-client associations:", error);
                    useToast().error('Failed to assign offers to clients.');
                }
            }
        }
    };
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style>
    .multiselect__tag {
        background-color: #81c950;
    }
    .multiselect__option--highlight{
        background-color: #81c950;
    }
    .multiselect__option--selected.multiselect__option--highlight{
        background-color: indianred;
    }
</style>
<style scoped lang="scss">
    .form-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: space-between;
    }
    .dark-gray {
        background-color: $dark-gray;
        justify-content: left;
        align-items: center;
        min-height: 20vh;
        min-width: 80vh;
    }
    .sub-title {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        color: $white;
    }
    .btn {
        padding: 9px 12px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        border-radius: 2px;
        background-color: $green;
    }
    .option, input, option, select, textarea {
        color: black;
    }
</style>
