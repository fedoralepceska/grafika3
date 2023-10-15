<template>
    <MainLayout>
        <div  class="pl-7 pr-7">
            <div class="header pt-10 pb-4">
                <div class="left mr-10">
                    <img src="/images/UserLogo.png" alt="UserLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">Client</h1>
                    <h3 class="text-white">Client / Add New Client</h3>
                </div>
            </div>
            <div class="dark-gray client-form ">
                <div class="form-container p15">
                    <h2 class="sub-title">
                        CLIENT DETAILS
                    </h2>
                    <form @submit.prevent="addClient">
                        <!-- Form fields for Name, Company, Email, and Phone -->
                        <div class="form-group">
                            <label for="name">Client:</label>
                            <input type="text" id="name" class="text-gray-700" v-model="client.name" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Contact Name:</label>
                            <input type="text" id="name" class="text-gray-700" v-model="client.company" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Contact Number:</label>
                            <input type="text" id="name" class="text-gray-700" v-model="client.phone" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Email:</label>
                            <input type="text" id="name" class="text-gray-700" v-model="client.email" required>
                        </div>
                        <!-- Other form fields... -->
                        <div class="button-container mt-10">
                            <PrimaryButton type="submit">Add Client</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";


export default {
    components: {MainLayout, PrimaryButton},
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
            axios.defaults.baseURL = "http://127.0.0.1:8000";
            axios
                .post('/clients', this.client)
                .then((response) => {
                    // Handle successful response
                    console.log('Client added successfully:', response.data);


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
.header{
    margin-left: 20px;
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 50vh;
}

.client-form {
    width: 100%;
    max-width: 600px;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 34px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;

}

.image-icon {
    margin-left: 10px;
    max-width: 50px; /* Adjust the size as needed */
}
.form-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex;
    justify-content: end;
}


</style>
