/*global Vue, _*/
import InvoicesDownload from '../../components/invoices-download.vue';
import InvoicePayments from '../../components/invoice-payments.vue';

// @vue/component
const vm = {

    el: '#invoicemaker-invoices',

    name: 'Invoices',

    components: {
        'invoices-download': InvoicesDownload,
        'invoice-payments': InvoicePayments,
    },

    data() {
        return _.merge({
            invoices: false,
            config: {
                filter: this.$session.get('bixie.invoicemaker.invoices.filter', {
                    order: 'invoice_number desc',
                    account_manager_id: null,
                    search: '',
                    status: '',
                    only_open: 0,
                    template: '',
                    exported: '',
                    invoice_group: '',
                    limit: 20,
                }),
            },
            pages: 0,
            count: '',
            roles: [],
            types: {},
            statuses: {},
            selected: [],
            moderators: [],
        }, window.$data);
    },

    computed: {
        boolOptions() {
            return [{text: this.$trans('Yes'), value: 1,}, {text: this.$trans('No'), value: -1,},];
        },
        templateOptions() {

            const options = this.templates.map(function (template) {
                return {text: template.name, value: template.name,};
            });
            return [{label: this.$trans('Filter by'), options,},];
        },

        statusOptions() {

            const options = [];
            _.forEach(this.statuses, (status, key) => {
                options.push({value: key, text: status,});
            });
            return [{label: this.$trans('Filter by'), options,},];
        },

        groupOptions() {

            const options = this.groups.map(function (group) {
                return {text: group.name, value: group.name,};
            });

            return [{label: this.$trans('Filter by'), options,},];
        },

        accountManagersOptions() {
            const options = [];
            _.forEach(this.moderators, (moderator, key) => {
                options.push({value: key, text: moderator.name,});
            });
            return [{label: this.$trans('Filter by'), options,},];
        },

        total_amount() {
            return this.invoices ? this.invoices.reduce((sum, invoice) => sum + Number(invoice.amount), 0) : 0;
        },

        total_open() {
            return this.invoices ? this.invoices.reduce((sum, invoice) => sum + Number(invoice.amount_open), 0) : 0;
        },


    },

    watch: {

        'config.filter': {
            handler: function (filter) {
                if (this.config.page) {
                    this.config.page = 0;
                } else {
                    this.load();
                }

                this.$session.set('bixie.invoicemaker.invoices.filter', filter);
            },
            deep: true,
        },

    },

    created() {
        this.Invoices = this.$resource('api/invoicemaker/invoice{/id}');
        this.$watch('config.page', this.load, {immediate: true,});
        console.log(this.moderators);
    },

    methods: {

        load() {
            return this.Invoices.query(this.config).then(res => {
                this.$set('invoices', res.data.invoices);
                this.$set('pages', res.data.pages);
                this.$set('count', res.data.count);
            });
        },

        active(invoice) {
            return this.selected.indexOf(invoice.id) !== -1;
        },

        getSelected() {
            return this.invoices.filter(function (invoice) {
                return this.selected.indexOf(invoice.id) !== -1;
            }, this);
        },

        getTypeLabel(name) {
            return this.types[name] ? this.types[name].label : name;
        },

        getStatusText(status) {
            return this.statuses[status] || status;
        },

        nrPayments(invoice) {
            return _.size(invoice.payments);
        },

        save(invoice) {
            return this.Invoices.save({id: invoice.id,}, {invoice,}).then(() => {
                this.load();
                this.$notify('Invoice saved.');
            });
        },

        removeInvoices() {
            this.Invoices.delete({id: 'bulk',}, {ids: this.selected,}).then((request) => {
                this.load();
                if (request.data.message == 'error') {
                    this.$notify('Er is een fout opgetreden.');
                } else {
                    this.$notify('Invoices(s) deleted.');
                }

            });
        },

        accountManagerName(invoice) {
            // Check if invoice has account manager id
            if (invoice.account_manager_id) {
                // Look for the manager in the moderators list
                const moderator = this.moderators.find(m => m.id === invoice.account_manager_id);
                return moderator.name;
            }
        },

    },

};

Vue.ready(vm);
