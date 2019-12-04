<?php $view->script('invoices-invoicemaker', 'bixie/invoicemaker:app/bundle/invoicemaker-invoices.js', ['bixie-pkframework']) ?>

<div id="invoicemaker-invoices" class="uk-form uk-form-horizontal" v-cloak>

    <div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
        <div class="uk-flex uk-flex-middle uk-flex-wrap" data-uk-margin>

            <h2 class="uk-margin-remove">{{ 'Invoices' | trans }}</h2>

            <div class="uk-margin-left" v-show="selected.length">
                <ul class="uk-subnav pk-subnav-icon">
                    <li><a class="pk-icon-delete pk-icon-hover" :title="'Delete' | trans"
                           data-uk-tooltip="{delay: 500}" @click.prevent="removeInvoices"
                           v-if="true" v-confirm="'Delete invoice?' | trans"></a>
                    </li>
                </ul>
            </div>

            <div class="uk-margin-left">

                <input-filter :title="$trans('Template')" :value.sync="config.filter.template"
                              :options="templateOptions"></input-filter>

                <input-filter :title="$trans('Group')" class="uk-margin-small-left"
                              :value.sync="config.filter.invoice_group" :options="groupOptions"></input-filter>

            </div>

            <div class="pk-search">
                <div class="uk-search">
                    <input class="uk-search-field" type="text" v-model="config.filter.search" debounce="300">
                </div>
            </div>

        </div>
        <invoices-download></invoices-download>
    </div>

    <div class="uk-overflow-container uk-form">
        <table class="uk-table uk-table-hover uk-table-middle">
            <thead>
            <tr>
                <th class="pk-table-width-minimum"><input type="checkbox" v-check-all:selected.literal="input[name=id]"
                                                          number></th>
                <th class="pk-table-min-width-100" v-order:invoice_number="config.filter.order">{{ 'Invoice #' | trans
                    }}
                </th>
                <th class="pk-table-min-width-200">{{ 'Debtor name' | trans }}</th>
                <th class="" v-order:ext_key="config.filter.order">{{ 'External key' | trans }}</th>
                <th class="pk-table-min-width-100">
                    <input-filter :title="$trans('Status')" :value.sync="config.filter.status"
                                  :options="statusOptions"></input-filter>
                </th>
                <th v-order:created="config.filter.order">{{ 'Date' | trans }}</th>
                <th class="pk-table-min-width-100" v-order:amount="config.filter.order">{{ 'Amount' | trans }}</th>
                <th class="pk-table-width-minimum"><i class="uk-icon-money" :title="$trans('Payments')"
                                                      data-uk-tooltip></i></th>
                <th class="pk-table-width-150 uk-text-center">
                    <input-filter :title="$trans('Exported')" :value.sync="config.filter.exported"
                                  :options="boolOptions"></input-filter>
                </th>
                <th class="pk-table-min-width-100">
                    <span v-order:amount_open="config.filter.order">{{ 'Open' | trans }}</span>
                    <label class="uk-text-small" :title="$trans('Show only invoices with an amount open')"
                           data-uk-tooltip="delay: 200">
                        <input type="checkbox" class="uk-margin-small-right"
                               v-model="config.filter.only_open" :true-value="1" :false-value="0" number/>!= 0</label>
                </th>
                <th>{{ 'PDF' | trans }}</th>
            </tr>
            </thead>
            <tbody>
            <tr class="check-item" v-for="invoice in invoices" :class="{'uk-active': active(invoice)}">
                <td><input type="checkbox" name="id" value="{{ invoice.id }}" number></td>
                <td>
                    <a :href="$url.route('admin/invoicemaker/invoice/edit', { id: invoice.id })">{{
                        invoice.invoice_number }}</a><br/>
                </td>
                <td>
                    {{ invoice.debtor.name }}<br/>
                    <small>{{ invoice.debtor.company }}</small>

                    <div class="uk-position-relative uk-float-right"
                         data-uk-dropdown="pos:'bottom-right', mode: 'hover', delay: 200">
                        <a><strong><i class="uk-icon-info-circle"></i></strong></a>
                        <div class="uk-dropdown">
                            <div v-if="invoice.debtor.company">
                                <i class="uk-icon-building-o uk-icon-justify"></i>{{ invoice.debtor.company }}
                            </div>
                            <div v-if="invoice.debtor.email">
                                <i class="uk-icon-envelope-o uk-icon-justify"></i>
                                <a :href="`mailto:invoice.debtor.email`">{{ invoice.debtor.email }}</a>
                            </div>
                            <div v-if="invoice.debtor.phone">
                                <i class="uk-icon-phone uk-icon-justify"></i>{{ invoice.debtor.phone }}
                            </div>
                        </div>
                    </div>

                </td>
                <td>
                    <em>{{ invoice.ext_key }}</em>
                </td>
                <td>
                    {{ getStatusText(invoice.status) }}
                </td>
                <td>
                    {{ invoice.created | date 'shortDate' }}
                </td>
                <td class="uk-text-nowrap uk-text-right">
                    {{ invoice.amount | currency '€ ' }}
                </td>
                <td>
                    <div class="uk-position-relative"
                         data-uk-dropdown="pos:'bottom-right', mode: 'click'">
                        <a><strong :title="$trans('Add/view payments')" data-uk-tooltip="delay: 200">
                                {{ nrPayments(invoice) }}
                            </strong></a>
                        <div class="uk-dropdown">
                            <invoice-payments :invoice.sync="invoice" :on-save="save"></invoice-payments>
                        </div>
                    </div>
                </td>
                <td class="uk-text-center">
                     <span>
                         <i v-if="invoice.exported" class="uk-icon-check uk-icon-justify uk-text-success"></i>
                         <i v-else class="uk-icon-times uk-icon-justify uk-text-danger"></i>
                     </span>
                </td>
                <td class="uk-text-nowrap uk-text-right">
                    {{ invoice.amount_open | currency '€ ' }}
                </td>
                <td>
                    <a :href="invoice.pdf_url" :title="invoice.pdf_filename" data-uk-tooltip="" download>
                        <i class="uk-icon-download uk-margin-small-right"></i></a>
                    <a :href="$url(invoice.pdf_url, {inline: 1})" class="uk-margin-small-right"
                       :title="invoice.pdf_filename" data-uk-tooltip="" data-uk-lightbox="" data-lightbox-type="iframe">
                        <i class="uk-icon-search uk-margin-small-right"></i></a>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="6"></td>
                <td class="uk-text-right"><strong>{{ total_amount | currency '€ ' }}</strong></td>
                <td></td>
                <td></td>
                <td class="uk-text-right"><strong>{{ total_open | currency '€ ' }}</strong></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <h3 class="uk-h1 uk-text-muted uk-text-center" v-show="invoices && !invoices.length">{{ 'No invoices found.' | trans
        }}</h3>

    <div class="uk-flex uk-flex-middle">
        <div class="uk-flex-item-1">
            <v-pagination :page.sync="config.page" :pages="pages" v-show="pages > 1"></v-pagination>
        </div>
        <div class="uk-margin-small-left">
            <select v-model="config.filter.limit" class="uk-form-small">
                <option value="20">20</option>
                <option value="40">40</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
        </div>
    </div>

</div>
