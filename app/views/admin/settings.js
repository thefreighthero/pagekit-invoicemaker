module.exports = {

    el: '#invoicemaker-settings',

    data: function () {
        return _.merge({
            config: {},
            form: {}
        }, window.$data);
    },

    methods: {

        save: function () {
            this.$http.post('admin/system/settings/config', { name: 'bixie/invoicemaker', config: this.config }).then(function () {
                this.$notify('Settings saved.');
            }, function (res) {
                this.$notify(res.data, 'danger');
            });
        },
        addGroup: function () {
            this.config.invoice_groups.push({
                name: '',
                format: '#{invoice_number}',
                digits: 4
            });
        },
        addTemplate: function () {
            this.config.templates.push({
                name: '',
                view: '',
                title: '',
                creditor_address: '',
                subline: '',
                params: {}
            });
        }

    }

};

Vue.ready(module.exports);
