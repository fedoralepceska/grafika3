<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="Individual Orders" icon="invoice.png" link="individual"/>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        Физичко лице
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter Order Number or Order Name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchOrders" />
                            <button class="btn create-order1" @click="searchOrders">Search</button>
                        </div>
                        <div class="flex gap-3">
                        <div class="status">
                            <label class="pr-3">Filter Status</label>
                            <select v-model="status" class="text-black" @change="applyFilter">
                                <option value="" hidden>Status</option>
                                <option value="">All Status</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div class="completion-status">
                            <label class="pr-3">Completion</label>
                            <select v-model="completionStatus" class="text-black" @change="applyFilter">
                                <option value="" hidden>Completion</option>
                                <option value="">All</option>
                                <option value="Completed">Completed</option>
                                <option value="In progress">In Progress</option>
                                <option value="Not started yet">Not Started</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black" @change="applyFilter">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div v-if="orders.data">
                        <div class="border mb-1" v-for="order in orders.data" :key="order.id">
                            <div class="bg-white text-black flex justify-between items-center p-2">
                                <div class="bold">Order #{{order.id}} - {{ Number(order.total_amount).toFixed(2) }} MKD</div>
                                <div class="note-header" @click="openNoteModal(order)">
									<span v-if="order.notes" class="note-preview" :title="order.notes">{{ order.notes }}</span>
                                    <span :class="order.notes ? 'text-green-500' : 'text-gray-400'" class="text-lg cursor-pointer">📝</span>
                                </div>
                            </div>
                            <div class="flex gap-6 p-3">
                                <div class="info">
                                    <div>Invoice</div>
                                    <div class="bold">#{{order.invoice_id}}</div>
                                </div>
                                <div class="info">
                                    <div>Completion</div>
                                    <div class="bold" :class="order.completion_status === 'Completed' ? 'green-text' : 'blue-text'">{{order.completion_status}}</div>
                                </div>
                                <div class="info">
                                    <div>Date</div>
                                    <div>{{ new Date(order.created_at).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) }}</div>
                                </div>
                                <div class="info">
                                    <div>Payment</div>
                                    <select v-model="localStatus[order.id]"
                                            class="text-black p-1"
                                            :disabled="!order.is_completed"
                                            @change="updateStatus(order.id, localStatus[order.id])">
                                        <option value="paid">Paid</option>
                                        <option value="unpaid">Unpaid</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="orders" @pagination-change-page="handlePageChange"/>
            </div>
        </div>
    </MainLayout>

    <!-- Note Modal -->
    <div v-if="showNoteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="dark-gray p-6 rounded-lg max-w-md w-full mx-4 text-white">
            <h3 class="text-lg font-bold mb-4 text-white">Order Notes</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-white mb-2">Order #{{ selectedOrder?.id }}</label>
                <textarea 
                    v-model="noteText" 
                    class="w-full p-3 border border-gray-600 rounded-md h-32 resize-none bg-gray-800 text-white placeholder-gray-400"
                    placeholder="Add notes for this order..."
                ></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button 
                    @click="closeNoteModal" 
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                >
                    Cancel
                </button>
                <button 
                    @click="saveNote" 
                    class="px-4 py-2 bg-green text-white rounded hover:bg-green-600"
                >
                    Save Note
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import Pagination from "@/Components/Pagination.vue"
import axios from 'axios';
import RedirectTabs from "@/Components/RedirectTabs.vue";
import { useToast } from "vue-toastification";

export default {
    components: {Header, MainLayout, Pagination, RedirectTabs},
    props:{
        orders:Object,
    },
    data() {
        return {
            searchQuery: '',
            sortOrder: 'desc',
            status: '',
            completionStatus: '',
            localStatus: {},
            localOrders: [],
            showNoteModal: false,
            selectedOrder: null,
            noteText: '',
        };
    },
    methods: {
        async applyFilter() {
            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('searchQuery', this.searchQuery);
                if (this.sortOrder) params.append('sortOrder', this.sortOrder);
                if (this.status) params.append('status', this.status);
                if (this.completionStatus) params.append('completionStatus', this.completionStatus);
                
                const queryString = params.toString();
                const url = queryString ? `/individual?${queryString}` : '/individual';
                
                this.$inertia.visit(url);
            } catch (error) {
                console.error(error);
            }
        },
        async searchOrders() {
            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('searchQuery', this.searchQuery);
                if (this.sortOrder) params.append('sortOrder', this.sortOrder);
                if (this.status) params.append('status', this.status);
                if (this.completionStatus) params.append('completionStatus', this.completionStatus);
                
                const queryString = params.toString();
                const url = queryString ? `/individual?${queryString}` : '/individual';
                
                this.$inertia.visit(url);
            } catch (error) {
                console.error(error);
            }
        },
        async updateStatus(id, paid_status) {
            const toast = useToast();
            try {
                // Check if this is an uncompleted order (temp ID)
                if (id.toString().startsWith('temp_')) {
                    toast.error('Cannot update status for uncompleted orders');
                    return;
                }
                
                await axios.put(`/individual/${id}/status`, { paid_status });
                // Update local status immediately for better UX
                this.localStatus[id] = paid_status;
                toast.success('Payment status updated successfully');
            } catch (e) { 
                console.error(e);
                toast.error('Failed to update payment status');
            }
        },
        handlePageChange(page) {
            const params = new URLSearchParams();
            if (this.searchQuery) params.append('searchQuery', this.searchQuery);
            if (this.sortOrder) params.append('sortOrder', this.sortOrder);
            if (this.status) params.append('status', this.status);
            if (this.completionStatus) params.append('completionStatus', this.completionStatus);
            params.append('page', page);
            
            const queryString = params.toString();
            const url = queryString ? `/individual?${queryString}` : '/individual';
            
            this.$inertia.visit(url);
        },
        openNoteModal(order) {
            this.selectedOrder = order;
            this.noteText = order.notes || '';
            this.showNoteModal = true;
        },
        closeNoteModal() {
            this.showNoteModal = false;
            this.selectedOrder = null;
            this.noteText = '';
        },
        async saveNote() {
            const toast = useToast();
            try {
                // Check if this is a temporary ID (uncompleted order)
                if (this.selectedOrder.id.toString().startsWith('temp_')) {
                    toast.error('Cannot save notes for uncompleted orders');
                    this.closeNoteModal();
                    return;
                }

                await axios.put(`/individual/${this.selectedOrder.id}/notes`, { 
                    notes: this.noteText 
                });
                
                // Update local data
                this.selectedOrder.notes = this.noteText;
                toast.success('Note saved successfully');
                
                this.closeNoteModal();
            } catch (e) {
                console.error('Error saving note:', e);
                toast.error('Failed to save note');
            }
        }
    },
    created() {
        // Initialize local status from props
        if (this.orders && this.orders.data) {
            this.localStatus = Object.fromEntries(this.orders.data.map(o => [o.id, o.paid_status]));
        }
    }
}
</script>

<style scoped lang="scss">
.info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;

}
.filter-container{
    justify-content: space-between;
}
select{
    width: 25vh;
    border-radius: 3px;
}
.blue-text{
    color: $blue;
}
.bold{
    font-weight: bold;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.green:hover{
    background-color: green;
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
    min-width: 80vh;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.note-header{
    display: flex;
    align-items: center;
    gap: 8px;
    max-width: 50%;
    cursor: pointer;
}
.note-preview{
    display: inline-block;
    max-width: 40vw;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #4b5563; // neutral text
}

.button-container{
    display: flex;
    justify-content: end;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.create-order1{
    background-color: $blue;
    color: white;
}
.create-order{
    background-color: $green;
    color: white;
}
</style>


