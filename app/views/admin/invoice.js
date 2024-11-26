/*global Vue, _*/
import InvoicePayments from '../../components/invoice-payments.vue';
import InvoiceAccountingEntries from '../../components/invoice-accounting-entries.vue';

// @vue/component
const vm = {

    el: '#invoice-edit',

    name: 'Invoice',

    components: {
        'invoice-payments': InvoicePayments,
        'invoice-accounting-entries': InvoiceAccountingEntries,
    },

    data: () => _.merge({
        invoice: {
            debtor: {},
            invoice_lines: [],
            payments: [],
            data: {
                notes: '',
            },
        },
        statuses: [],
        templates: [],
        ledger_numbers: [],
        tfConfig: {},
        form: {},
    }, window.$data),

    computed: {
        keys() {
            return (this.types[this.invoice.type] ? this.types[this.invoice.type].keys : []);
        },
    },

    created() {
        if (!_.isArray(this.invoice.payments)) {
            this.invoice.payments = [];
        }
        if (!_.isArray(this.invoice.data.ledger_data)) {
            this.invoice.data = {...this.invoice.data, ...{ledger_data: [],},};
        }
        this.invoice.amount = Number(this.invoice.amount);
        this.invoice.amount_paid = Number(this.invoice.amount_paid);
    },

    ready() {
        this.Invoices = this.$resource('api/invoicemaker/invoice{/id}', {}, {
            'rerender': {method: 'get', url: 'api/invoicemaker/invoice/rerender{/id}',},
            'credit': {method: 'post', url: 'api/invoicemaker/invoice/credit{/id}',},
        });
    },

    methods: {

        save() {

            if(this.invoice.exported == true) {
                this.$notify(this.$trans('Invoice %invoice_number% is already exported. Be careful what you change.', {invoice_number: this.invoice.invoice_number,}), 'danger');
                // this.$notify(this.$trans('Invoice %invoice_number% cannot be saved. The invoice is already exported.', {invoice_number: this.invoice.invoice_number,}), 'danger');
                // return false;
            }
            this.Invoices.save({id: this.invoice.id,}, {invoice: this.invoice,}).then(res => {
                const data = res.data;
                if (!this.invoice.id) {
                    window.history.replaceState({}, '', this.$url.route('admin/invoicemaker/invoice/edit', {id: data.invoice.id,}));
                }

                this.$set('invoice', data.invoice);

                this.$notify(this.$trans('Invoice %invoice_number% saved.', {invoice_number: this.invoice.invoice_number,}));

                this.$els.iframe.contentWindow.location.reload();

            }, res => {
                this.$notify(res.data || res, 'danger');
            });
        },

        rerender() {
            this.Invoices.rerender({id: this.invoice.id,}).then(() => {
                this.$notify('PDF rerendered.', 'success');
            }, res => {
                this.$notify(res.data || res, 'danger');
            });
        },

        credit() {
            this.Invoices.credit({id: this.invoice.id,}, {}).then((res) => {
                this.$notify(this.$trans('Credit invoice %invoice_number% created.', {
                    'invoice_number': res.data.credit_invoice.invoice_number,
                }), 'success');

                console.log(this.$url.route('admin/invoicemaker/invoice/edit', {id: res.data.credit_invoice.id,}));
                window.location = this.$url.route('admin/invoicemaker/invoice/edit', {id: res.data.credit_invoice.id,});

            }, res => this.$notify(res.data.message || res.data, 'danger'));


        },

        getStatusText(status) {
            return this.statuses[status] || status;
        },

        amountDebetCredit(invoice, amount) {
            return invoice.status === 'CREDIT' || (invoice.amount <= 0 && amount > 0) ? Number(amount) * -1: Number(amount);
        },

        amountPaidFromInvoice(invoice, invoice_revenue) {
            const amount_paid = _.isArray(invoice.payments) ? invoice.payments.reduce((s, pymt) => {
                if (!pymt.from_credit_invoice) {
                    return s + Number(pymt.amount);
                }
                return s;
            }, 0) : 0;
            //clamp to cut off vat and customs
            if (amount_paid < 0) {
                return Math.max(amount_paid, invoice_revenue.revenue_amount * -1);
            }
            return Math.min(amount_paid, invoice_revenue.revenue_amount);
        },
    },

};

Vue.ready(vm);
