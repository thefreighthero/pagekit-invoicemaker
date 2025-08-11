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
                <!-- Info with:
                 Debitor name, Account manager name, Company link  -->
                <th class="pk-table-min-width-200">
                    <input-filter :title="$trans('Info')" :value.sync="config.filter.account_manager_id"
                                  :options="accountManagersOptions"></input-filter>
                </th>
                <th>
                    <input-filter :title="$trans('External key')" :value.sync="config.filter.ext_key"
                                  :options="externalKeysOptions"></input-filter>
                <th class="pk-table-min-width-100">
                    <input-filter :title="$trans('Status')" :value.sync="config.filter.status"
                                  :options="statusOptions"></input-filter>
                </th>
                <th v-order:due_date="config.filter.order">{{ 'Dates' | trans }}</th>
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
                        invoice.invoice_number }}</a>
                    <em>{{ invoice.booking_type && invoice.booking_type.trim() !== '' ? invoice.booking_type : 'Geen
                        booking type aangegeven!' }}</em>
                    <small v-if="invoice.data.hidden">{{ 'Verborgen v. klant' | trans }}</small>
                </td>
                <td>
                    <!-- Debtor name and company -->
                    {{ invoice.debtor.name }}<br/>

                    <!-- Company link -->
                    <a target="_blank"
                       :href="'/admin/contactmanager/company/edit?id=' + invoice.company_id"
                       @click.stop>
                        <small>{{ invoice.debtor.company }}</small>
                    </a>

                    <!-- Account manager -->
                    <p class="uk-margin-remove uk-text-small">{{ accountManagerName(invoice) || 'Geen account manager
                        gekkopeld' }}</p>

                    <div class="uk-position-relative uk-float-right"
                         data-uk-dropdown="pos:'bottom-right', mode: 'click', delay: 200" @click.stop>
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

                            <!--Account manager select -->
                            <div>
                                <div v-if="isCmCompany(invoice.ext_key)">
                                    <select v-model="invoice.account_manager_id" @change="save(invoice, true)"
                                            class="uk-form-width-medium">
                                        <option :value="" disabled>{{ 'Geen acccount manager' | trans }}</option>
                                        <option v-for="(id, moderator) in moderators" :value="moderator.id">{{
                                            moderator.name }}
                                        </option>
                                    </select>
                                </div>
                                <div v-if="!isCmCompany(invoice.ext_key)">
                                    <p class="uk-text-italic uk-text-small">
                                        Account manager kan niet gewijzigd worden voor facturen van verzending! Ga naar
                                        <a target="_blank"
                                           :href="'/admin/freighthero/shipment/edit?id=' + extractShipmentId(invoice.ext_key)"
                                           @click.stop>
                                            verzending
                                        </a>
                                        om dit aan te passen!
                                    </p>
                                </div>
                            </div>

                            <!--Booking type select -->
                            <div>
                                <select id="form-assigned" class="uk-width-1-1" v-model="invoice.booking_type" @change="save(invoice, true)">
                                    <option :value="" disabled>{{ 'Geen booking type' | trans }}</option>
                                    <option v-for="(type, label) in booking_types" :value="type">{{ label }}
                                    </option>
                                </select>
                            </div>

                        </div>
                    </div>

                </td>
                <td>
                    <!-- Link to shipment if ext key is  -->
                    <a target="_blank" v-if="!isCmCompany(invoice.ext_key)"
                       :href="'/admin/freighthero/shipment/edit?id=' + extractShipmentId(invoice.ext_key)"
                       @click.stop class="uk-flex">
                        <strong><i class="uk-icon-truck uk-margin-small-right"></i></strong>
                        {{ invoice.ext_key }}
                    </a>
                    <a target="_blank" v-if="isCmCompany(invoice.ext_key)"
                       :href="'/admin/contactmanager/company/edit?id=' + invoice.company_id"
                       @click.stop class="uk-flex">
                        <strong><i class="uk-icon-phone uk-margin-small-right"></i></strong>
                        {{ invoice.ext_key }}
                    </a>
                </td>
                <td>
                    {{ getStatusText(invoice.status) }}
                </td>
                <td>
                    Gemaakt: <strong>{{ invoice.created | date 'shortDate' }}</strong>
                    Vervaldatum: <strong>{{ invoice.data.due_date | date 'shortDate' }}</strong>
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
                    <a :href="$url(invoice.pdf_url, {inline: 1, v: Date.now(),})" class="uk-margin-small-right"
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
