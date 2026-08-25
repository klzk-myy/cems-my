<x-app-layout title="Transaction Wizard">
    <div class="space-y-6">
        <x-page-header title="New Transaction" description="Step-by-step transaction creation wizard" />

        <div x-data="{
            step: 1,
            totalSteps: 4,
            formData: {
                customer_id: '',
                currency_code: '',
                transaction_type: '',
                amount_foreign: '',
                rate: '',
                amount_local: '',
                source_of_funds: '',
                purpose_of_transaction: '',
                branch_id: '',
                purpose: '',
                id_number: '',
                id_type: '',
                customer_name: '',
                date_of_birth: '',
                nationality: '',
                document_type: '',
            },
            loading: false,
            errorMessage: '',
            init() {
                this.$watch('formData.amount_foreign', () => this.calculateLocal());
                this.$watch('formData.rate', () => this.calculateLocal());
            },
            calculateLocal() {
                const foreign = parseFloat(this.formData.amount_foreign) || 0;
                const rate = parseFloat(this.formData.rate) || 0;
                this.formData.amount_local = (foreign * rate).toFixed(2);
            },
            nextStep() {
                if (this.step < this.totalSteps) this.step++;
            },
            prevStep() {
                if (this.step > 1) this.step--;
            },
            submitStep() {
                this.loading = true;
                this.errorMessage = '';
            }
        }" class="max-w-4xl mx-auto">

            <!-- Step Indicator -->
            <div class="flex items-center justify-between mb-8">
                <template x-for="i in totalSteps" :key="i">
                    <div class="flex items-center" :class="i < totalSteps ? 'flex-1' : ''">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all"
                             :class="step >= i ? 'bg-primary text-white border-primary' : 'border-gray-300 text-gray-400'">
                            <span x-text="i"></span>
                        </div>
                        <div x-show="i < totalSteps" class="flex-1 h-0.5 mx-2"
                             :class="step > i ? 'bg-primary' : 'bg-gray-200'"></div>
                    </div>
                </template>
            </div>

            <!-- Step 1: Transaction Details -->
            <div x-show="step === 1" class="bg-white border border-[#e5e5e5] rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-4">Step 1: Transaction Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Customer ID</label>
                        <input type="number" x-model="formData.customer_id" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Currency</label>
                        <select x-model="formData.currency_code" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">Select currency</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="SGD">SGD</option>
                            <option value="JPY">JPY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Transaction Type</label>
                        <select x-model="formData.transaction_type" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">Select type</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Foreign Amount</label>
                        <input type="number" step="0.01" x-model="formData.amount_foreign" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Rate</label>
                        <input type="number" step="0.0001" x-model="formData.rate" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Local Amount (MYR)</label>
                        <input type="text" x-model="formData.amount_local" readonly class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Source of Funds</label>
                        <input type="text" x-model="formData.source_of_funds" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Purpose</label>
                        <input type="text" x-model="formData.purpose_of_transaction" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Step 2: KYC Verification -->
            <div x-show="step === 2" class="bg-white border border-[#e5e5e5] rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-4">Step 2: KYC Verification</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Customer Name</label>
                        <input type="text" x-model="formData.customer_name" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Type</label>
                        <select x-model="formData.id_type" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">Select ID type</option>
                            <option value="mykad">MyKad</option>
                            <option value="passport">Passport</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" x-model="formData.id_number" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Date of Birth</label>
                        <input type="date" x-model="formData.date_of_birth" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Nationality</label>
                        <input type="text" x-model="formData.nationality" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Step 3: Document Upload -->
            <div x-show="step === 3" class="bg-white border border-[#e5e5e5] rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-4">Step 3: Document Upload</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Document Type</label>
                        <select x-model="formData.document_type" class="w-full px-4 py-2.5 text-sm border border-[#e5e5e5] rounded-lg">
                            <option value="">Select document</option>
                            <option value="mykad_front">MyKad Front</option>
                            <option value="mykad_back">MyKad Back</option>
                            <option value="passport">Passport</option>
                            <option value="proof_of_address">Proof of Address</option>
                        </select>
                    </div>
                    <div class="border-2 border-dashed border-[#e5e5e5] rounded-lg p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm text-gray-500">Drop files here or click to upload</p>
                        <p class="text-xs text-gray-400 mt-1">PDF, JPG, JPEG, PNG (max 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Review & Confirm -->
            <div x-show="step === 4" class="bg-white border border-[#e5e5e5] rounded-xl p-6">
                <h2 class="text-lg font-semibold mb-4">Step 4: Review & Confirm</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2 border-b border-[#e5e5e5]">
                        <span class="text-ink-muted">Customer ID</span><span x-text="formData.customer_id || '—'"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-[#e5e5e5]">
                        <span class="text-ink-muted">Type</span><span x-text="formData.transaction_type || '—'"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-[#e5e5e5]">
                        <span class="text-ink-muted">Currency</span><span x-text="formData.currency_code || '—'"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-[#e5e5e5]">
                        <span class="text-ink-muted">Foreign Amount</span><span x-text="formData.amount_foreign || '—'"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-[#e5e5e5]">
                        <span class="text-ink-muted">Rate</span><span x-text="formData.rate || '—'"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-[#e5e5e5] font-bold">
                        <span class="text-ink-muted">Local Amount (MYR)</span><span x-text="formData.amount_local || '—'"></span>
                    </div>
                </div>

                <div x-show="errorMessage" class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm" x-text="errorMessage"></div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-6">
                <button @click="prevStep()" :disabled="step === 1"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-[#e5e5e5] disabled:opacity-50">
                    Previous
                </button>
                <button x-show="step < totalSteps" @click="nextStep()"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-[#0a0a0a] text-white hover:bg-[#262626]">
                    Next
                </button>
                <button x-show="step === totalSteps" @click="submitStep()"
                        :disabled="loading"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-[#0a0a0a] text-white hover:bg-[#262626] disabled:opacity-50">
                    <span x-show="!loading">Submit Transaction</span>
                    <span x-show="loading">Processing...</span>
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
