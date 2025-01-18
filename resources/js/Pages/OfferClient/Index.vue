<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between">
                <Header title="offerClient" subtitle="offerClient" icon="Materials.png" link="offer-client"/>
                <div class="flex pt-4">
                    <div class="flex gap-2 pt-3">
                        <button class="btn" @click="navigateToCreate">Create offer for client</button>
                    </div>
                </div>
            </div>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <table class="excel-table mb-3 ">
                            <thead>
                            <tr>
                                <th style="width: 45px;">{{$t('Nr')}}</th>
                                <th>{{$t('offer')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                <th>{{$t('client')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                <th>{{$t('ACTIONS')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, index) in offers_clients" :key="index">
                                    <tr>
                                        <th>{{ index + 1 }}</th>
                                        <th>{{ item.offer_name }}</th>
                                        <th>{{ item.client_name }}</th>
                                        <th v-if="item.is_accepted !== null">
                                            <span :class="item.is_accepted ? 'text-green-500' : 'text-red-500'">
                                                {{ item.is_accepted ? 'Accepted' : 'Declined' }}
                                            </span>
                                        </th>
                                        <th v-else>
                                            <PrimaryButton @click="acceptOffer(item.id, true)" type="submit">{{ $t('accept') }}</PrimaryButton>
                                            <button class="btn"><DeclineOfferDialog :offer_client_id="item.id" /></button>
                                        </th>
                                        <th>
                                            <div class="centered">
                                                <OfferClientInfoDialog :offer_client_id="item.id" />
                                            </div>
                                        </th>
                                    </tr>
                                </template>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import Pagination from "@/Components/Pagination.vue"
import Header from "@/Components/Header.vue";
import AddWarehouseDialog from "@/Components/AddWarehouseDialog.vue";
import ViewWarehousesDialog from "@/Components/ViewWarehousesDialog.vue";
import axios from "axios";
import DangerButton from "@/Components/DangerButton.vue";
import {useToast} from "vue-toastification";
import DeclineOfferDialog from "@/Components/DeclineOfferDialog.vue";
import OfferClientInfoDialog from "@/Components/OfferClientInfoDialog.vue";


export default {
    name: 'Index',
    components: {
        OfferClientInfoDialog,
        DeclineOfferDialog,
        DangerButton,
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        Pagination,
        Header,
        AddWarehouseDialog,
        ViewWarehousesDialog,
    },
    props: {
        offers_clients: Object,
    },
    mounted() {
        console.log(this.$props.offers_clients);
    },
    methods: {
        initResize(event, index) {
            this.startX = event.clientX;
            this.startWidth = event.target.parentElement.offsetWidth;
            this.columnIndex = index;

            document.documentElement.addEventListener('mousemove', this.resizeColumn);
            document.documentElement.addEventListener('mouseup', this.stopResize);
        },
        resizeColumn(event) {
            const diffX = event.clientX - this.startX;
            const newWidth = this.startWidth + diffX;
            const ths = document.querySelectorAll('.excel-table th');

            if (this.columnIndex >= 0 && this.columnIndex < ths.length) {
                ths[this.columnIndex].style.width = `${newWidth}px`;
            }
        },
        stopResize() {
            document.documentElement.removeEventListener('mousemove', this.resizeColumn);
            document.documentElement.removeEventListener('mouseup', this.stopResize);
        },
        async acceptOffer(id, param) {
            try {
                await axios.post('/offer-client/accept', { accept: param, offer_client: id });
                const acceptedLabel = param ? 'accepted' : 'declined';

                useToast().success(`Offers successfully ${acceptedLabel}!`);
                window.location.reload();
            } catch (error) {
                useToast().error('Failed to accept offer.');
            }
        },
        navigateToCreate() {
            this.$inertia.visit(`/offer-client/create`);
        }
    },
};
</script>

<style scoped lang="scss">
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.btn {
    padding: 5px 10px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    color: white;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.light-gray{
    background-color: $light-gray;
}
.excel-table {
    border-collapse: collapse;
    width: 100%;
    color: white;
    table-layout: fixed;
}
.excel-table th,
.excel-table td {
    border: 1px solid #dddddd;
    padding: 4px;
    text-align: center;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    position: relative;
}
.excel-table th {
    min-width: 50px;
}
.excel-table tr:nth-child(even) {
    background-color: $ultra-light-gray;
}
.resizer {
    width: 5px;
    height: 100%;
    position: absolute;
    right: 0;
    top: 0;
    cursor: col-resize;
    user-select: none;
    background-color: transparent;
}
.info {
    border: 2px solid white;
    min-width: 90vh;
    max-width: 100vh;
}
</style>
