<template>
    <div class="background">
        <h1>Add New Client</h1>
        <form @submit.prevent="addClient">
            <!-- Form fields for Name, Company, Email, and Phone -->
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" v-model="client.name" required>
            </div>
            <div class="form-group">
                <label for="name">Company:</label>
                <input type="text" id="name" v-model="client.company" required>
            </div>
            <div class="form-group">
                <label for="name">Email:</label>
                <input type="text" id="name" v-model="client.email" required>
            </div>
            <div class="form-group">
                <label for="name">Phone:</label>
                <input type="text" id="name" v-model="client.phone" required>
            </div>

            <!-- Other form fields... -->
            <PrimaryButton type="submit">Add Client</PrimaryButton>
        </form>
    </div>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default {
    components: {PrimaryButton},
    data() {
        return {
            client: {
                name: '',
                company: '',
                email: '',
                phone: '',
            },
        };
    },
    methods: {
        addClient() {
            axios.defaults.baseURL="http://127.0.0.1:8000";
            axios
                .post('/clients', this.client)
                .then((response) => {
                    // Handle successful response
                    console.log('Client added successfully:', response.data);

                    // You can redirect or display a success message
                })
                .catch((error) => {
                    // Handle errors, including validation errors
                    console.error('Error adding client:', error);

                });
        },
    },
};
</script>
<style scoped lang="scss">

</style>
