module.exports = {

    name: 'invoices',

    el: '#invoicemaker-invoices',

    data() {
        return _.merge({
            invoices: false,
            config: {
                filter: this.$session.get('bixie.invoicemaker.invoices.filter', {
                    order: 'invoice_number desc',
                    search: '',
                    status: '',
                    only_open: 0,
                    template: '',
                    invoice_group: '',
                    limit: 20,
                })
            },
            pages: 0,
            count: '',
            roles: [],
            types: {},
            statuses: {},
            selected: []
        }, window.$data);
    },

    created() {
        this.Invoices = this.$resource('api/invoicemaker/invoice{/id}');
        this.$watch('config.page', this.load, {immediate: true});
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
            return this.invoices.filter(function (invoice) { return this.selected.indexOf(invoice.id) !== -1; }, this);
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
            return this.Invoices.save({id: invoice.id}, {invoice}).then(() => {
                this.load();
                this.$notify('Invoice saved.');
            });
        },

        removeInvoices() {
            this.Invoices.delete({id: 'bulk'}, {ids: this.selected}).then(() => {
                this.load();
                this.$notify('Invoices(s) deleted.');
            });
        }

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
            deep: true
        }

    },

    computed: {

        templateOptions: function () {

            var options = this.templates.map(function (template) {
                return {text: template.name, value: template.name};
            });
            return [{label: this.$trans('Filter by'), options: options}];
        },

        statusOptions: function () {

            var options = [];
            _.forEach(this.statuses, (status, key) => {
                options.push({value: key, text: status});
            });
            return [{label: this.$trans('Filter by'), options: options}];
        },

        groupOptions: function () {

            var options = this.groups.map(function (group) {
                return {text: group.name, value: group.name};
            });

            return [{label: this.$trans('Filter by'), options: options}];
        },

        total_amount() {
            return this.invoices ? this.invoices.reduce((sum, invoice) => sum + Number(invoice.amount), 0) : 0;
        },

        total_open() {
            return this.invoices ? this.invoices.reduce((sum, invoice) => sum + Number(invoice.amount_open), 0) : 0;
        },

    },

    components: {
        'invoices-download': require('../../components/invoices-download.vue'),
        'invoice-payments': require('../../components/invoice-payments.vue'),
    },


};

Vue.ready(module.exports);

