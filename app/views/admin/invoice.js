module.exports = {

    el: '#invoice-edit',

    data: function () {
        return _.merge({
            invoice: {
                debtor: {},
                invoice_lines: [],
                data: {}
            },
            templates: [],
            form: {}
        }, window.$data);
    },

    ready: function () {
        this.Invoices = this.$resource('api/invoicemaker/invoice{/id}', {}, {'rerender': {
            method: 'get',
            url: 'api/invoicemaker/invoice/rerender{/id}'
        }});
    },

    computed: {
        keys: function () {
            return (this.types[this.invoice.type] ? this.types[this.invoice.type].keys : []);
        }
    },

    methods: {

        save: function () {
            this.Invoices.save({id: this.invoice.id}, {invoice: this.invoice}).then(function (res) {
                var data = res.data;
                if (!this.invoice.id) {
                    window.history.replaceState({}, '', this.$url.route('admin/invoicemaker/invoice/edit', {id: data.invoice.id}));
                }

                this.$set('invoice', data.invoice);

                this.$notify(this.$trans('Invoice %invoice_number% saved.', {invoice_number: this.invoice.invoice_number}));

                this.$els.iframe.contentWindow.location.reload();

            }, function (res) {
                this.$notify(res.data || res, 'danger');
            });
        },

        rerender: function () {
            this.Invoices.rerender({id: this.invoice.id}).then(function () {
                this.$notify('PDF rerendered.', 'success');
            }, function (res) {
                this.$notify(res.data || res, 'danger');
            });
        }

    }

};

Vue.ready(module.exports);
