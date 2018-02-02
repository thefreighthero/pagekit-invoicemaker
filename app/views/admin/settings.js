/*global Vue, _*/

// @vue/component
const vm = {

    el: '#invoicemaker-settings',

    name: 'InvoicemakerSettings',

    data: () => _.merge({
        config: {},
        form: {},
    }, window.$data),

    methods: {

        save() {
            this.$http.post('admin/system/settings/config', {name: 'bixie/invoicemaker', config: this.config,})
                .then(() => this.$notify('Settings saved.'), res => this.$notify(res.data, 'danger'));
        },
        addGroup() {
            this.config.invoice_groups.push({
                name: '',
                format: '#{invoice_number}',
                digits: 4,
            });
        },
        addTemplate() {
            this.config.templates.push({
                name: '',
                view: '',
                title: '',
                creditor_address: '',
                subline: '',
                params: {},
            });
        },

    },

};

Vue.ready(vm);
