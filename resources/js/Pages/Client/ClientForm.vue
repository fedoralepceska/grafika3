<template>
    <MainLayout>
        <div  class="pl-7 pr-7">
            <div class="header pt-3 pb-4">
                <div class="left mr-3">
                    <img src="/images/UserLogo.png" alt="UserLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">{{ $t('client') }}</h1>
                    <h3 class="text-white"><span class="green-text">{{ $t('client') }}</span> / {{ $t('addNewClient') }}</h3>
                </div>
            </div>
            <div class="dark-gray p-5 ">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('clientDetails') }}
                    </h2>
                    <form @submit.prevent="addClient">
                        <!-- Form fields for Name, Company, Email, and Phone -->
                        <div class="form-group">
                            <label for="name" class="mr-4 width100">{{ $t('company') }}</label>
                            <input type="text" id="name" class="text-gray-700" v-model="client.name" required>
                        </div>
                        <div class="form-group">
                            <label for="address" class="mr-4 width100">{{ $t('address') }}</label>
                            <input type="text" id="address" class="text-gray-700" v-model="client.address" required>
                        </div>
                        <div class="form-group">
                            <label for="city" class="mr-4 width100">{{ $t('city') }}</label>
                            <input type="text" id="city" class="text-gray-700" v-model="client.city" required>
                        </div>
                        <div class="mt-12">
                            <h2 class="sub-title">{{ $t('otherContacts') }}</h2>
                            <div v-for="(contact, index) in client.contacts" :key="index">
                                <div class="flex mt-2 p-2 text-black">
                                    <label class="label-fixed-width">{{ $t('contact') }} {{ index + 1 }}</label>
                                    <!-- Fields for contact details -->
                                    <input v-model="contact.name" class="contact-input" type="text" placeholder="Name">
                                    <input v-model="contact.email" class="contact-input" type="email" placeholder="Email">
                                    <input v-model="contact.phone" class="contact-input" type="text" placeholder="Phone">
                                    <button class="addBtn" @click="addContact" v-if="index === client.contacts.length - 1">
                                        <span class="mdi mdi-plus-circle"></span>
                                    </button>
                                    <button class="removeBtn" @click="removeContact(index)" v-else>
                                        <span class="mdi mdi-minus-circle"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Other form fields... -->
                        <div class="button-container mt-10">
                            <PrimaryButton type="submit">{{ $t('addClient') }}</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import {useToast} from "vue-toastification";

export default {
    components: {MainLayout, PrimaryButton},
    data() {
        return {
            client: {
                name: '',
                address: '',
                city: '',
                contacts: [
                    { name: '', email: '', phone: '' }
                ]
            },
        };
    },
    methods: {
        addClient() {
            const toast = useToast();
            axios
                .post('/clients', this.client)
                .then((response) => {
                    toast.success("Client added successfully.")

                })
                .catch((error) => {
                    toast.error("Error adding client!")

                });
        },
        addContact() {
            this.client.contacts.push({ name: '', email: '', phone: '' });
        },
        removeContact(index) {
            this.client.contacts.splice(index, 1);
        },

    },
};
</script>
<style scoped lang="scss">
select[data-v-375938f7], option[data-v-375938f7], input[data-v-375938f7]{
    border-radius: 3px;
}
.green-text{
    color: $green;
}
.light-gray{
    background-color: $light-gray;
}
.header{
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
}

.client-form {
    width: 100%;
    max-width: 1000px;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px; /* Adjust the size as needed */
}
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 350px;
    margin-bottom: 10px;
    color: $white;
    padding-left: 10px;
}
.label {
    flex: 1;
    text-align: left;
    margin-right: 21px;
}
.button-container{
    display: flex;
    justify-content: end;
}

.addBtn {
    color: $light-green;
    margin-left: 8px;
    cursor: pointer;
}

.removeBtn {
    color: #c95050;
    margin-left: 7px;
    cursor: pointer;
}

label {
    color: $white;
}

select, option, input {
    color: black;
}

.contact-input {
    width: 50%;
}

.label-fixed-width {
    width: 11rem;
    align-self: center;
}

.width100 {
    width: 100px;
}

</style>
