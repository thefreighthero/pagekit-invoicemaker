module.exports = {

    name: 'invoices',

    el: '#invoicemaker-invoices',

    data: function () {
        return _.merge({
            invoices: false,
            config: {
                filter: this.$session.get('bixie.invoicemaker.invoices.filter', {order: 'invoice_number desc', search: '', template: '', invoice_group: ''})
            },
            pages: 0,
            count: '',
            roles: [],
            types: {},
            selected: []
        }, window.$data);
    },

    created: function () {
        this.Invoices = this.$resource('api/invoicemaker/invoice{/id}');
        this.$watch('config.page', this.load, {immediate: true});
    },

    methods: {

        load: function () {
            return this.Invoices.query(this.config).then(function (res) {
                this.$set('invoices', res.data.invoices);
                this.$set('pages', res.data.pages);
                this.$set('count', res.data.count);
            });
        },

        active: function (invoice) {
            return this.selected.indexOf(invoice.id) !== -1;
        },

        getSelected: function () {
            return this.invoices.filter(function (invoice) { return this.selected.indexOf(invoice.id) !== -1; }, this);
        },

        getTypeLabel: function (name) {
            return this.types[name] ? this.types[name].label : name;
        },

        getRoles: function (invoice) {
            var roles_invoice = this.$trans('All roles');
            if (invoice.roles.length && invoice.roles.length !== this.roles.length) {
                roles_invoice = invoice.roles.map(function (id) {
                    return _.find(this.roles, 'id', id).name;
                }, this).join(', ');
            }
            return roles_invoice;
        },

        removeInvoices: function () {

            this.Invoices.delete({id: 'bulk'}, {ids: this.selected}).then(function () {
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

        groupOptions: function () {

            var options = this.groups.map(function (group) {
                return {text: group.name, value: group.name};
            });

            return [{label: this.$trans('Filter by'), options: options}];
        }

    }


};

Vue.ready(module.exports);

