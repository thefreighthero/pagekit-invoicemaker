<template>

    <div>

        <div class="uk-form-row">
            <a @click="add_payment = true"><i class="uk-icon-plus uk-margin-small-right"></i>{{ 'Add payment' | trans }}</a>

            <div v-if="add_payment">
                <div class="uk-flex uk-flex-middle uk-flex-right">
                    <i class="uk-icon-euro uk-margin-right"></i>
                    <input type="number" v-model="new_payment.amount" class="uk-width-8-10 uk-text-right"
                           :placeholder="$trans('Amount')" step="0.01" number/>
                </div>

                <input-date-bix :datetime.sync="new_payment.date" class="uk-width-1-1 uk-margin-small-top" :show-time="false"></input-date-bix>

                <input type="text" v-model="new_payment.via" class="uk-width-1-1 uk-margin-small-top" :placeholder="$trans('Via')"/>

                <input type="text" v-model="new_payment.transaction_id" class="uk-width-1-1 uk-margin-small-top" :placeholder="$trans('Transaction ID')"/>

                <p class="uk-text-right">
                    <button type="button" @click="add" class="uk-button uk-button-small">{{ 'Add' | trans }}</button>
                </p>
            </div>
        </div>

        <ul class="uk-list uk-list-line">
            <invoice-payment v-for="payment in invoice.payments" :payment.sync="payment"></invoice-payment>
        </ul>

    </div>

</template>
<script>

    export default {

        name: 'InvoicePayments',

        props: {'invoice': Object, 'onSave': Function,},

        data() {
            return {
                add_payment: false,
                new_payment: {
                    amount: this.invoice.amount_open,
                    date: new Date(),
                    via: '',
                    transaction_id: '',
                },
            };
        },

        watch: {
            'invoice.payments': {handler: function () {
                    this.invoice.amount_paid = _.reduce(this.invoice.payments, (sum, payment) => sum + Number(payment.amount), 0);
                }, deep:  true, immediate: true,},
        },

        created() {
            if (!_.isArray(this.invoice.payments)) {
                this.invoice.payments = [];
            }
        },

        methods: {
            add() {
                if (!this.new_payment.amount) {
                    return;
                }
                this.invoice.payments.push(_.merge({}, this.new_payment));
                this.new_payment = {
                    amount: this.invoice.amount_open,
                    date: new Date(),
                    via: '',
                    transaction_id: '',
                };
                this.save();
                this.add_payment = false;
            },
            remove(payment) {
                this.invoice.payments.$remove(payment);
                this.save();
            },
            save() {
                this.$nextTick(() => this.onSave(this.invoice));
            },
        },

        components: {
            'invoice-payment': {

                name: 'InvoicePayment',

                props: {'payment': Object,},

                data() {
                    return {
                        edit: false,
                    }
                },

                methods: {
                    save() {
                        this.edit = false;
                        this.$parent.save();
                    },
                },

                template: `<li>
                <div v-if="edit">
                    <div class="uk-flex uk-flex-middle uk-flex-right">
                        <i class="uk-icon-euro uk-margin-right"></i>
                        <input type="number" v-model="payment.amount" class="uk-width-8-10 uk-text-right"
                               :placeholder="$trans('Amount')" step="0.01" number/>
                    </div>

                    <input-date-bix :datetime.sync="payment.date" class="uk-width-1-1 uk-margin-small-top" :show-time="false"></input-date-bix>

                    <input type="text" v-model="payment.via" class="uk-width-1-1 uk-margin-small-top" :placeholder="$trans('Via')"/>

                    <input type="text" v-model="payment.transaction_id" class="uk-width-1-1 uk-margin-small-top" :placeholder="$trans('Transaction ID')"/>

                    <p class="uk-margin-small uk-text-right">
                        <button type="button" @click="save" class="uk-button uk-button-small">{{ 'Save' | trans }}</button>
                    </p>
                </div>
                <div v-else>
                    <p class="uk-margin-small uk-flex uk-flex-middle uk-flex-space-between">
                       <span>{{ payment.date | date 'shortDate'}}</span>
                       <span>{{ payment.amount | currency '€ ' }}
                         <small class="uk-margin-small-left">
                         <a @click="edit = true" class="uk-icon-edit"></a>
                         <a @click="$parent.remove(payment)" class="uk-icon-trash-o uk-margin-small-left"></a>
                         </small>
                     </span>
                    </p>
                    <div class="uk-flex uk-flex-space-between">
                        <small v-if="payment.via">{{ payment.via }}</small>
                        <small v-if="payment.transaction_id">{{ payment.transaction_id }}</small>
                   </div>
                </div>
            </li>`,

            },
        },
    };


</script>