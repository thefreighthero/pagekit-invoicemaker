<?php
$view->script('invoice-edit', 'bixie/invoicemaker:app/bundle/invoicemaker-invoice.js',
    ['bixie-pkframework']);
$iframe_src = $app->url('@invoicemaker/api/invoice/html', [
	'invoice_number' => $invoice->invoice_number,
	'key' => $app->module('bixie/invoicemaker')->getDownloadKey($invoice)
]);
?>

<div id="invoice-edit" v-cloak>
	<form class="uk-form" v-validator="form" @submit.prevent="save | valid">

		<div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
			<div data-uk-margin>

				<h2 class="uk-margin-remove">{{ 'Edit invoice' | trans }} <em>{{ invoice.invoice_number }}</em></h2>
				<em>{{ 'External key' | trans }}:</em> <span>{{ invoice.ext_key }}</span><br/>
				<em>{{ 'Status' | trans }}:</em> <span>{{ getStatusText(invoice.status) }}</span><br/>
                <span v-if="invoice.data.credit_for">
                    <em>{{ 'Credit for' | trans }}: </em> <a :href="$url.route('admin/invoicemaker/invoice/edit', { id: invoice.data.credit_for_id })" target="_blank"> <i class="uk-icon-external-link uk-margin-small-right"></i>{{ invoice.data.credit_for }}</a>
                </span>


			</div>
			<div data-uk-margin>

				<a class="uk-button uk-margin-small-right" :href="$url.route('admin/invoicemaker')">{{ invoice.id ?
					'Close' :
					'Cancel' | trans }}</a>
				<button class="uk-button uk-button-primary" type="submit">{{ 'Save' | trans }}</button>

			</div>
		</div>

		<div class="uk-grid" data-uk-grid-margin>
			<div class="uk-width-large-2-3">

				<iframe v-el:iframe src="<?=$iframe_src?>" frameborder="0" class="uk-responsive-width" width="800" height="1130"></iframe>

                <div class="uk-form-row uk-margin">
                    <label class="uk-form-label">{{ 'Internal notes' | trans }}</label>
                    <div class="uk-form-controls">
                        <textarea v-model="invoice.data.notes"
                                  cols="30" rows="6"
                                  class="uk-width-1-1"></textarea>
                    </div>
                </div>


                <div>

                    <div class="uk-panel uk-panel-box">
                        <h3 class="uk-panel-title">Gekoppelde inkoopfacturen</h3>
                        <div class="uk-form">
                            <table class="uk-table uk-table-hover uk-table-middle">
                                <thead>
                                <tr>
                                    <th>{{ 'PDF' | trans }}</th>
                                    <th class="pk-table-min-width-100">{{ 'Date / Ledger number' | trans }}<br>{{ 'Company' | trans }}</th>
                                    <th class="pk-table-min-width-100">{{ 'Invoice number' | trans }}<br>{{ 'Description' | trans }}</th>
                                    <th class="pk-table-width-150 uk-text-center">
                                        {{ 'Status' | trans }}
                                    </th>
                                    <th class="pk-table-min-width-100 uk-text-right">{{ 'Amount' | trans }}<br>{{ 'Open' | trans }}<br>{{ 'Dispute' | trans }}</label>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr class="check-item" v-for="purchase_invoice in purchase_invoices">

                                    <td>
                                        <a v-if="purchase_invoice.pdf_file" :href="purchase_invoice.pdf_url" download>
                                            <i class="uk-icon-download uk-margin-small-right"></i></a>
                                        <a v-if="purchase_invoice.pdf_file" :href="$url(purchase_invoice.pdf_url, {inline: 1})" class="uk-margin-small-right"
                                           data-uk-lightbox="" data-lightbox-type="iframe">
                                            <i class="uk-icon-search uk-margin-small-right"></i></a>
                                    </td>
                                    <td>
                                        {{ purchase_invoice.date | date }}<br/>
                                        <small>{{ purchase_invoice.ledger_number }}</small>
                                        <small v-if="purchase_invoice.api_client !== 'master'"> - {{ purchase_invoice.api_client }}</small><br>
                                        <a v-if="purchase_invoice.company_url" :href="purchase_invoice.company_url"
                                           target="_blank"><i class="uk-icon-external-link uk-margin-small-right"></i></a>
                                        {{ purchase_invoice.company_name }}
                                    </td>
                                    <td>
                                        <a :href="$url.route('admin/twinfield/purchase_invoice/edit', { id: purchase_invoice.id })">
                                            {{ purchase_invoice.invoice_number || '-' }}</a> <small
                                                v-if="purchase_invoice.siblings_count > 1">({{ purchase_invoice.siblings_count }})</small>
                                        <br>
                                        {{ purchase_invoice.description }}
                                        <a v-if="purchase_invoice.shipment_id" :href="purchase_invoice.shipment_url" target="_blank"
                                           class="uk-display-block uk-text-small">
                                            <i class="uk-icon-external-link uk-margin-small-right"></i>
                                            #{{ purchase_invoice.shipment_id }}
                                        </a>
                                    </td>
                                    <td class="uk-text-center">
                                        <span class="uk-margin-small-right" :title="$trans('Exported')" data-uk-tooltip="delay:200">
                                            <span>
                                                <i v-if="purchase_invoice.exported" class="uk-icon-check uk-icon-justify uk-text-success"></i>
                                                <i v-else class="uk-icon-times uk-icon-justify uk-text-danger"></i>
                                            </span>
                                        </span>
                                        <br><span class="uk-margin-small-right" :title="$trans('Approved')" data-uk-tooltip="delay:200">
                                            <a @click="toggleValue('approved', purchase_invoice)">
                                                <i v-if="purchase_invoice.approved" class="uk-icon-check uk-icon-justify uk-text-success"></i>
                                                <i v-else class="uk-icon-times uk-icon-justify uk-text-danger"></i>
                                            </a>
                                        </span>
                                        <br><span class="uk-margin-small-right" :title="$trans('Paid')" data-uk-tooltip="delay:200">
                                            <i v-if="purchase_invoice.paid" class="uk-icon-check uk-icon-justify uk-text-success"></i>
                                            <i v-else class="uk-icon-times uk-icon-justify uk-text-danger"></i>
                                        </span>
                                    </td>
                                    <td class="uk-text-nowrap uk-text-right">
                                        {{ purchase_invoice.amount | currency '€ ' }}<br/>
                                        <small v-if="purchase_invoice.siblings_count > 1">({{ purchase_invoice.invoice_number_amount | currency '€ ' }})</small>
                                        {{ purchase_invoice.amount_open | currency '€ ' }}<br>
                                        {{ purchase_invoice.amount_dispute | currency '€ ' }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="uk-panel uk-panel-box">
                        <h3 class="uk-panel-title">Cash in</h3>
                        <table class="uk-form uk-table uk-table-middle uk-margin-top-remove">
                            <tr>
                                <th class="uk-text-right pk-table-max-width-100">{{ 'Amount' | trans }}</th>
                                <th class="uk-text-right">{{ 'Marge €' | trans }}</th>
                                <th class="uk-text-right">{{ 'Marge %' | trans }}</th>
                                <th class="uk-text-right">{{ 'Omzet' | trans }}</th>
                                <th class="uk-text-right">{{ 'Betaald' | trans }}</th>
                            </tr>
                            <tbody>
                            <tr >
                                <td class="uk-text-right pk-table-max-width-100 uk-text-nowrap">
                                    {{ amountDebetCredit(invoice, invoice_revenue.revenue_amount) | currency }}
                                </td>
                                <td class="uk-text-right pk-table-max-width-100 uk-text-nowrap">
                                    {{ amountDebetCredit(invoice, shipment.price - shipment.cost_price) | currency }}

                                </td>
                                <td class="uk-text-right pk-table-max-width-100 uk-text-nowrap">
                                    {{ amountDebetCredit(invoice, shipment.bruto_margin) }}%
                                </td>
                                <td class="uk-text-right pk-table-max-width-100 uk-text-nowrap">
                                    {{ amountDebetCredit(invoice, invoice_revenue.revenue_amount) | currency }}
                                </td>
                                <td class="uk-text-right pk-table-max-width-100 uk-text-nowrap">
                                    {{ amountPaidFromInvoice(invoice, invoice_revenue) | currency }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
			<div class="uk-width-large-1-3">

				<div class="uk-form-row">
					<span class="uk-form-label">{{ 'PDF File' | trans }}</span>
					<div class="uk-form-controls uk-form-controls-text">
						<a :href="invoice.pdf_url" :title="$trans('Download')" data-uk-tooltip download>
                            <i class="uk-icon-download uk-margin-small-right"></i></a>
                        <a :href="$url(invoice.pdf_url, {inline: 1, v: Date.now(),})" class="uk-margin-small-right"
                           :title="$trans('View')" data-uk-tooltip data-uk-lightbox="" data-lightbox-type="iframe">
                            <i class="uk-icon-search uk-margin-small-right"></i></a>
							{{ invoice.pdf_filename }}
					</div>
				</div>

				<div v-if="invoice.pdf_file" class="uk-form-row">
					<span class="uk-form-label">{{ 'PDF file' | trans }}</span>
					<div class="uk-form-controls uk-text-right">
						<button type="button" class="uk-button" @click="rerender">{{ 'Rerender PDF file' | trans }}</button>
					</div>
				</div>

				<div class="uk-form-row">
					<label class="uk-form-label">{{ 'Invoice template' | trans }}</label>
					<div class="uk-form-controls">
						<select v-model="invoice.template" class="uk-form-width-medium">
							<option v-for="template in templates" :value="template.name">{{ template.name }}</option>
						</select>
					</div>
				</div>

                <div v-if="invoice.status === 'INITIAL'" class="uk-form-row">
                    <span class="uk-form-label">{{ 'Credit invoice' | trans }}</span>
                    <div class="uk-form-controls uk-text-right">
                        <button type="button" class="uk-button" @click="credit">{{ 'Duplicate to credit invoice' | trans }}</button>
                    </div>
                </div>

                <h3>{{ 'Ledger data' | trans }}</h3>

                <div class="uk-alert uk-alert-danger" v-if="invoice.exported">
                    {{ 'Factuur is reeds geexporteerd. Je mag geen bedragen meer aanpassen.' | trans }}</div>
                <invoice-accounting-entries class="uk-margin" :entries.sync="invoice.data.ledger_data"
                                            :ledger_numbers="ledger_numbers"
                                            :vat_codes="tfConfig.vat_codes"></invoice-accounting-entries>

                <div class="uk-form-row uk-form-stacked">
                    <div class="uk-form-controls">
                        <p class="uk-form-controls-condensed">
                            <label><input type="checkbox" v-model="invoice.exported" disabled/>
                                {{ 'Exported' | trans }}
                            </label>
                            <a v-if="!invoice.exported"
                               :title="$trans('Block for export')" data-uk-tooltip="delay:300"
                               v-confirm="{title: $trans('Mark as exported manually?'), text: $trans('This will block the invoice for exporting to Twinfield.')}"
                               @click="invoice.exported = true">
                                <i class="uk-icon-ban uk-margin-small-left uk-text-danger"></i>
                            </a>
                        </p>
                    </div>
                </div>


                <h3>{{ 'Payments' | trans }}</h3>

                <invoice-payments :invoice.sync="invoice" :on-save="save"></invoice-payments>

                <hr/>

                <div class="uk-grid uk-grid-small" data-uk-grid-margin>
                    <div class="uk-width-2-3">
                        <p>Total amount</p>
                    </div>
                    <div class="uk-width-1-3 uk-text-right">
                        <span>{{ invoice.amount | currency '€ ' }}</span>
                    </div>
                </div>
                <div class="uk-grid uk-grid-small uk-margin-small-top" data-uk-grid-margin>
                    <div class="uk-width-2-3">
                        <p>Paid amount</p>
                    </div>
                    <div class="uk-width-1-3 uk-text-right">
                        <span>{{ invoice.amount_paid | currency '€ ' }}</span>
                    </div>
                </div>
                <div v-if="invoice.amount_open" class="uk-grid uk-grid-small uk-margin-small-top" data-uk-grid-margin>
                    <div class="uk-width-2-3">
                        <p>Open amount</p>
                    </div>
                    <div class="uk-width-1-3 uk-text-right">
                        <strong>{{ invoice.amount_open | currency '€ ' }}</strong>
                    </div>
                </div>
                <div v-else class="uk-grid uk-grid-small uk-margin-small-top" data-uk-grid-margin>
                    <div class="uk-width-2-3">
                        <p>Paid at</p>
                    </div>
                    <div class="uk-width-1-3 uk-text-right">
                        <strong>{{ (invoice.paid_at) | date }}</strong>
                    </div>
                </div>


				<h3>{{ 'Debtor' | trans }}</h3>

				<div class="uk-form-row">
					<label for="invoice-debtor-company" class="uk-form-label">{{ 'Company' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-company" name="company" class="uk-width-1-1" v-model="invoice.debtor.company"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-name" class="uk-form-label">{{ 'Name' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-name" name="name" class="uk-width-1-1" v-model="invoice.debtor.name"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-address_1" class="uk-form-label">{{ 'Address 1' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-address_1" name="address_1" class="uk-width-1-1" v-model="invoice.debtor.address_1"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-address_2" class="uk-form-label">{{ 'Address 2' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-address_2" name="address_2" class="uk-width-1-1" v-model="invoice.debtor.address_2"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-zip_code" class="uk-form-label">{{ 'Zip code' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-zip_code" name="zip_code" class="uk-width-1-1" v-model="invoice.debtor.zip_code"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-city" class="uk-form-label">{{ 'City' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-city" name="city" class="uk-width-1-1" v-model="invoice.debtor.city"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-country" class="uk-form-label">{{ 'Country' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-country" name="country" class="uk-width-1-1" v-model="invoice.debtor.country"/>
					</div>
				</div>
				<hr/>
				<div class="uk-form-row">
					<label for="invoice-debtor-email" class="uk-form-label">{{ 'Email' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-email" name="email" class="uk-width-1-1" v-model="invoice.debtor.email" v-validate:email/>
						<p class="uk-form-help-block uk-text-danger" v-show="form.email.invalid">{{ 'Please enter a valid email address' | trans }}</p>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-phone" class="uk-form-label">{{ 'Phone' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-phone" name="debtor_phone" class="uk-width-1-1" v-model="invoice.debtor.phone"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-debtor_id" class="uk-form-label">{{ 'Debtor ID' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-debtor_id" name="debtor_id" class="uk-width-1-1" v-model="invoice.debtor.debtor_id"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-debtor_vat" class="uk-form-label">{{ 'VAT number' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-debtor_vat" name="debtor_vat" class="uk-width-1-1" v-model="invoice.debtor.debtor_vat"/>
					</div>
				</div>
				<div class="uk-form-row">
					<label for="invoice-debtor-debtor_coc" class="uk-form-label">{{ 'COC number' | trans }}</label>
					<div class="uk-form-controls">
						<input id="invoice-debtor-debtor_coc" name="debtor_coc" class="uk-width-1-1" v-model="invoice.debtor.debtor_coc"/>
					</div>
				</div>

                <div class="uk-form-row">
                    <div v-if="invoice.ext_key && !invoice.ext_key.includes('tfh.shipment')">
                        <h3>{{ 'Externe key overschrijven' | trans }}</h3>
                        <div class="uk-form-row">
                            <label for="invoice-shipment_id" class="uk-form-label">{{ 'Shipment ID' | trans }}</label>
                            <div class="uk-form-controls">
                                <input id="invoice-shipment_id" name="shipment_id" class="uk-width-1-1" v-model="invoice.shipment_id"/>
                            </div>
                        </div>
                    </div>
                </div>
			</div>

		</div>

	</form>

</div>

