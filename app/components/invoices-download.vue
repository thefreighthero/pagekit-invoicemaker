<template>

    <div>

        <button @click="$refs.modal.open" type="button" class="uk-button">{{ 'Download invoices' | trans }}</button>


        <v-modal v-ref:modal>
            <a class="focus"></a>
            <div class="uk-form">
                <div class="uk-form-row">
                    <label class="uk-form-label">{{ 'Paid' | trans }}</label>
                    <div class="uk-form-controls">
                        <label><input v-model="filter.only_open" type="checkbox"
                                      class="uk-margin-small-right"/>{{ 'Only not paid' | trans }}</label>
                    </div>
                </div>
                <div class="uk-margin uk-grid uk-grid-width-medium-1-2" data-uk-grid-margin>
                    <div>

                        <div class="uk-form-row">
                            <label class="uk-form-label">{{ 'Date from' | trans }}</label>
                            <div class="uk-form-controls">
                                <input-date-bix v-ref:date_from :datetime.sync="filter.date_from"
                                                :show-time="false"></input-date-bix>
                            </div>
                        </div>
                    </div>
                    <div>

                        <div class="uk-form-row">
                            <label class="uk-form-label">{{ 'Date to' | trans }}</label>
                            <div class="uk-form-controls">
                                <input-date-bix v-ref:date_to :datetime.sync="filter.date_to"
                                                :show-time="false"></input-date-bix>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="uk-modal-footer uk-text-right">
                <a class="uk-button uk-modal-close">{{ 'Close' | trans }}</a>
                <a :href="downloadUrl" class="uk-button uk-button-primary uk-margin-small-left">
                    <i class="uk-icon-download uk-margin-small-right"></i>{{ 'Download invoices' | trans }}</a>
            </div>

        </v-modal>
    </div>

</template>
<script>

    module.exports = {

        name: 'invoices-download',

        data() {
            return {
                base_url: 'admin/invoicemaker/download',
                filter: {
                    date_from: new Date(),
                    date_to: new Date(),
                    only_open: 0,
                },
            }
        },

        computed: {
            downloadUrl() {
                return this.$url(this.base_url, {filter: this.filter});
            },
        },

    };


</script>