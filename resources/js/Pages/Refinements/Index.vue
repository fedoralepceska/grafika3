<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="refinements" subtitle="allRefinements" icon="screw.png" link="refinements"/>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <h2 class="sub-title">
                            {{ $t('allRefinements') }}
                        </h2>
                        <div class="flex justify-between items-center pr-3 pb-8 gap-4">
                            <div class="flex-1 max-w-md">
                                <input
                                    v-model="searchQuery"
                                    @input="debouncedSearch"
                                    @keyup.enter="applyFilter(1)"
                                    type="text"
                                    placeholder="Search by name or material..."
                                    class="w-full text-black px-4 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm">Per page</span>
                                <select v-model.number="perPage" @change="applyFilter(1)" class="text-black px-2 py-2 rounded border border-gray-300">
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                    <option :value="50">50</option>
                                </select>
                            </div>
                            <AddRefinementDialog :refinement="Refinements"/>
                        </div>
                        
                        <EditRefinementDialog 
                            ref="editDialog"
                            :refinement="currentEditingRefinement"
                            @refinement-updated="refreshRefinements"
                        />
                        
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                <tr>
                                    <th class="table-header id-column">{{$t('Nr')}}</th>
                                    <th class="table-header name-column">{{$t('name')}}</th>
                                    <th class="table-header material-column">{{$t('material')}}-{{$t('Quantity')}}</th>
                                    <th class="table-header unit-column">{{$t('Unit')}}</th>
                                    <th class="table-header action-column">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="refinement in refinements?.data || []" :key="refinement.id" class="table-row">
                                    <td class="table-cell id-cell">{{ refinement.id }}</td>
                                    <td class="table-cell name-cell">{{ refinement.name }}</td>
                                    <td class="table-cell material-cell">{{ getMaterial(refinement) }}</td>
                                    <td class="table-cell unit-cell">
                                        <span class="unit-badge" v-if="getUnit(refinement)">{{ getUnit(refinement) }}</span>
                                    </td>
                                    <td class="table-cell action-cell">
                                        <div class="action-buttons">
                                            <button 
                                                class="edit-btn" 
                                                @click="openEditDialog(refinement)"
                                                title="Edit refinement"
                                            >
                                                <svg class="edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Edit
                                            </button>
                                            <button 
                                                class="delete-btn" 
                                                @click="deleteRefinement(refinement)"
                                                title="Delete refinement"
                                            >
                                                <svg class="delete-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 px-3">
                            <Pagination
                                v-if="refinements && refinements.links"
                                :pagination="refinements"
                                @pagination-change-page="handlePageChange"
                            />
                        </div>
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
import axios from "axios";
import AddContactDialog from "@/Components/AddContactDialog.vue";
import ViewContactsDialog from "@/Components/ViewContactsDialog.vue";
import Pagination from "@/Components/Pagination.vue"
import Header from "@/Components/Header.vue";
import UpdateClientDialog from "@/Components/UpdateClientDialog.vue";
import CardStatementUpdateDialog from "@/Components/CardStatementUpdateDialog.vue";
import PriemInfoDialog from "@/Components/PriemInfoDialog.vue";
import AddRefinementDialog from "@/Components/AddRefinementDialog.vue";
import EditRefinementDialog from "@/Components/EditRefinementDialog.vue";

export default {
    components: {
        EditRefinementDialog,
        AddRefinementDialog,
        CardStatementUpdateDialog,
        UpdateClientDialog,
        ViewContactsDialog,
        AddContactDialog,
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        Pagination,
        Header,
        PriemInfoDialog
    },
    props: {
        refinements: Object,
        filters: Object,
    },
    data() {
        return {
            currentEditingRefinement: null,
            isOpeningDialog: false,
            searchQuery: this.filters?.search || '',
            perPage: this.filters?.perPage || 10,
            searchDebounceTimer: null,
        };
    },
    mounted() {
        // Ensure the component is properly mounted
        this.$nextTick(() => {
            console.log('Refinements Index component mounted');
        });
    },
    computed: {},
    methods: {
        visitWithFilters(page = 1) {
            const params = {
                search: this.searchQuery || undefined,
                per_page: this.perPage,
                page
            };
            this.$inertia.get('/refinements', params, { preserveState: true, preserveScroll: true, replace: true });
        },
        applyFilter(page = 1) {
            this.visitWithFilters(page);
        },
        debouncedSearch() {
            if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => this.applyFilter(1), 400);
        },
        handlePageChange(page) {
            this.visitWithFilters(page);
        },
        getUnit(refinement) {
            const small = refinement?.small_material;
            const large = refinement?.large_format_material;

            if (small !== null || large !== null) {
                if (small?.article?.in_meters === 1 || large?.article?.in_meters === 1) {
                    return 'meters'
                }
                else if (small?.article?.in_square_meters === 1 || large?.article?.in_square_meters === 1) {
                    return 'square meters'
                }
                else if (small?.article?.in_kilograms === 1 || large?.article?.in_kilograms === 1) {
                    return 'kilograms'
                }
                else if (small?.article?.in_pieces === 1 || large?.article?.in_pieces === 1) {
                    return 'pieces'
                }
            }
            else {
                return '';
            }
        },
        getMaterial(refinement) {
            if (refinement?.small_material) {
                return refinement?.small_material?.name + "-" + refinement?.small_material?.quantity;
            } else if (refinement?.large_format_material) {
                return refinement?.large_format_material?.name + "-" + refinement?.large_format_material?.quantity;
            } else {
                return "X";
            }
        },
        async openEditDialog(refinement) {
            if (!refinement) {
                console.warn('No refinement provided');
                return;
            }
            
            // Prevent multiple rapid clicks
            if (this.isOpeningDialog) {
                return;
            }
            
            this.isOpeningDialog = true;
            
            try {
                // Set the refinement first
                this.currentEditingRefinement = refinement;
                
                // Use $nextTick to ensure the DOM is fully rendered and the prop is updated
                await this.$nextTick();
                
                if (!this.$refs.editDialog) {
                    console.error('Edit dialog reference not found');
                    return;
                }
                
                // Add a small delay to ensure the component is fully ready
                await new Promise(resolve => setTimeout(resolve, 10));
                
                await this.$refs.editDialog.openDialog();
            } catch (error) {
                console.error('Error opening edit dialog:', error);
            } finally {
                this.isOpeningDialog = false;
            }
        },
        async refreshRefinements() {
            try {
                // Clear current editing refinement
                this.currentEditingRefinement = null;
                
                // Use Inertia's reload method to refresh page data without full page reload
                this.$inertia.reload({ only: ['refinements'] });
            } catch (error) {
                console.error('Error refreshing refinements:', error);
            }
        },
        
        async deleteRefinement(refinement) {
            if (!refinement) {
                console.warn('No refinement provided for deletion');
                return;
            }
            
            
            try {
                const response = await axios.delete(`/refinements/${refinement.id}`);
                
                // Show success message
                if (response.data.message) {
                    // You can add a toast notification here if you have a toast system
                    console.log(response.data.message);
                }
                
                // Refresh the refinements list
                await this.refreshRefinements();
                
            } catch (error) {
                console.error('Error deleting refinement:', error);
                // You can add an error toast notification here
                alert('Error deleting refinement. Please try again.');
            }
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
.filters{
    justify-content: space-between;
}
select{
    width: 240px;
}
.buttF{
    padding-top: 23.5px;
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
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.delete{
    border: none;
    color: white;
    background-color: $red;
}
.delete:hover{
    background-color: darkred;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
    border: none;
    color: white;
}
.blue:hover{
    background-color: cornflowerblue;
}
.green{
    background-color: $green;
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
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
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
    max-width: 40px;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
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

/* Modern Table Styles */
.table-container {
    border: 1px solid #dddddd;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-top: 20px;
}

.modern-table {
    width: 100%;
    border: 1px solid #dddddd;
    font-size: 14px;
    color: $white;
}

.table-header {
   
    color: white;
    font-weight: 600;
    padding: 16px 12px;
    text-align: left;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid #dddddd;
    position: relative;
}

.table-header:first-child {
    border-top-left-radius: 12px;
}

.table-header:last-child {
    border-top-right-radius: 12px;
}

.table-row {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}



.table-row:last-child {
    border-bottom: none;
}

.table-cell {
    padding: 16px 12px;
    text-align: left;
    vertical-align: middle;
    border: none;
    font-weight: 500;
}

/* Column-specific styles */
.id-column, .id-cell {
    width: 80px;
    text-align: center;
    font-weight: 600;
    color: $white;
}

.name-column, .name-cell {
    min-width: 200px;
    font-weight: 600;
    color: $white;
}

.material-column, .material-cell {
    min-width: 250px;
    color: $white;
}

.unit-column, .unit-cell {
    width: 120px;
    text-align: center;
}

.action-column, .action-cell {
    width: 160px;
    text-align: center;
}

/* Unit badge styling */
.unit-badge {
    background: $green;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    min-width: 80px;
}

/* Action buttons container */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

/* Edit button styling */
.edit-btn {
    background: $blue;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 4px rgba(66, 153, 225, 0.3);
}




.edit-icon {
    width: 14px;
    height: 14px;
}

/* Delete button styling */
.delete-btn {
    background: $red;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 4px rgba(245, 101, 101, 0.3);
}


.delete-icon {
    width: 14px;
    height: 14px;
}

/* Responsive design */
@media (max-width: 768px) {
    .table-container {
        border-radius: 8px;
        margin-top: 15px;
    }
    
    .table-header {
        padding: 12px 8px;
        font-size: 12px;
    }
    
    .table-cell {
        padding: 12px 8px;
        font-size: 13px;
    }
    
    .name-column, .name-cell {
        min-width: 150px;
    }
    
    .material-column, .material-cell {
        min-width: 180px;
    }
}
.info {
    border: 2px solid white;
    min-width: 90vh;
    max-width: 100vh;
}
.contact-info {
    display: flex;
    flex-direction: row;
    align-items: center;
}
</style>
