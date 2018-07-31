<template>

    <div>
        <p v-if="!isBalanced" class="uk-alert uk-alert-danger uk-text-center">
            {{ 'Amounts not in balance!' | trans }}
        </p>
        <div class="uk-grid uk-grid-small uk-grid-width-1-2">
            <div>
                <div class="uk-flex uk-flex-middle">
                    <div class="uk-flex-item-1"><h4 class="uk-margin-remove">{{ 'Credit' | trans }}</h4></div>
                    <div>
                        <strong :class="{'uk-text-danger': !isBalanced}">
                            {{ balanceAmounts.rounded.credit | currency '€ '}}
                        </strong>
                    </div>
                </div>

                <ul class="uk-nestable uk-margin-remove" v-show="creditEntries.length">
                    <datafield v-for="datafield in creditEntries"
                               :datafield="datafield"
                               @check:balance="checkBalance"></datafield>
                </ul>

                <button type="button" class="uk-button uk-button-small uk-margin"
                        @click="addDatafield('credit')">{{ 'Add to credit' | trans }}
                </button>
            </div>
            <div>
                <div class="uk-flex uk-flex-middle">
                    <div class="uk-flex-item-1"><h4 class="uk-margin-remove">{{ 'Debit' | trans }}</h4></div>
                    <div>
                        <strong :class="{'uk-text-danger': !isBalanced}">
                            {{ balanceAmounts.rounded.debit | currency '€ '}}
                        </strong>
                    </div>
                </div>
                <ul class="uk-nestable uk-margin-remove" v-show="debitEntries.length">
                    <datafield v-for="datafield in debitEntries"
                               :datafield="datafield"></datafield>
                </ul>

                <button type="button" class="uk-button uk-button-small uk-margin"
                        @click="addDatafield('debit')">{{ 'Add to debit' | trans }}
                </button>
            </div>

        </div>
    </div>

</template>

<script>
/*global _, UIkit */

export default {

    name: 'InvoiceAccountingEntries',

    components: {
        // @vue/component
        datafield: {
            name: 'Datafield',
            props: {'datafield': Object,},
            watch: {
                'datafield.ledger_number'(number) {
                    const ledger = _.find(this.$parent.ledger_numbers, {number,});
                    this.datafield.vat_code = ledger.vat_code;
                },
            },
            template: '<li class="uk-nestable-item" :data-ledger_number="datafield.ledger_number">\n    <div class="uk-nestable-panel uk-visible-hover uk-form">\n        <div class="uk-margin-small-top uk-text-right">\n            <i class="uk-icon-euro uk-margin-small-right"></i>\n            <input type="number" class="uk-width-4-5 uk-text-right" v-model="datafield.amount" step="0.01" number/>\n        </div>\n        <div class="uk-grid uk-grid-small uk-margin-small-top">\n            <div class="uk-width-1-2">\n                <select v-model="datafield.ledger_number" class="uk-width-1-1 uk-form-small">\n                    <option value="">{{ \'Ledger number\' | trans }}</option>\n                    <option v-for="ledger_number in $parent.ledger_numbers"\n                            :value="ledger_number.number">{{ ledger_number.number }} - {{ ledger_number.label }}</option>\n                </select>\n            </div>\n            <div class="uk-width-1-2">\n                <select v-model="datafield.vat_code" class="uk-width-1-1 uk-form-small">\n                    <option value="">{{ \'No Vat Code\' | trans }}</option>\n                    <option v-for="vat_code in $parent.vat_codes" :value="vat_code.code">{{ vat_code.code }} - {{ vat_code.label }}</option>\n                </select>\n            </div>\n        </div>\n        <div class="uk-margin-small-top">\n            <input type="text" class="uk-width-1-1"\n                   v-model="datafield.description" :placeholder="$trans(\'Description\')"/>\n        </div>\n        <p class="uk-form-help-block uk-text-danger" v-show="datafield.invalid">{{ datafield.invalid | trans }}</p>\n        <div class="uk-margin-small-top">\n            <ul class="uk-subnav uk-flex-right pk-subnav-icon">\n                <li><a class="pk-icon-delete pk-icon-hover uk-invisible" @click="$parent.removeDatafield(datafield)"></a></li>\n            </ul>\n        </div>\n    </div>\n</li>   \n',
        },

    },

    props: {
        entries: Array,
        ledger_numbers: Array,
        vat_codes: Array,
    },

    computed: {
        creditEntries() {
            return this.entries.filter(entry => entry.debit_credit === 'credit');
        },
        debitEntries() {
            return this.entries.filter(entry => entry.debit_credit === 'debit');
        },
        balanceAmounts() {
            const addEntryTotal = (sum, entry) => {
                const vat_code = _.find(this.vat_codes, {code: entry.vat_code,});
                let cent_amount = Math.round(entry.amount * 100);
                if (vat_code && vat_code.percentage) {
                    const vat_factor = 1 + (vat_code.percentage / 100);
                    cent_amount = Math.round(cent_amount * vat_factor)
                }
                return sum + cent_amount;
            };
            const credit = (this.creditEntries.reduce(addEntryTotal, 0));
            const debit = (this.debitEntries.reduce(addEntryTotal, 0));
            console.log(credit, debit);
            return {
                debit,
                credit,
                rounded: {
                    debit: debit / 100,
                    credit: credit / 100,
                },
            };
        },
        isBalanced() {
            return this.balanceAmounts.credit === this.balanceAmounts.debit;
        },
    },

    methods: {
        addDatafield(debit_credit) {
            this.entries.push({
                debit_credit,
                amount: Math.round(Math.abs((this.balanceAmounts.credit - this.balanceAmounts.debit))) / 100,
                ledger_number: '',
                vat_code: '',
                description: '',
                invalid: false,
            });
            this.$nextTick(() => UIkit.$(this.$els.accounting_entriesNestable).find('input:first').focus());
        },

        removeDatafield(idx) {
            this.entries.splice(idx, 1);
            this.checkDuplicates();
        },

        checkDuplicates() {
            let current, dups = [];
            _.sortBy(this.entries, 'ledger_number').forEach(datafield => {
                if (current && current === datafield.ledger_number) {
                    dups.push(datafield.ledger_number);
                }
                current = datafield.ledger_number;
            });
            this.entries.forEach(datafield => datafield.invalid = dups.indexOf(datafield.ledger_number) > -1 ? 'Duplicate ledger number' : false);
        },
    },

};

</script>
