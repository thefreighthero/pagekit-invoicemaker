<?php
$view->style('codemirror');
$view->script('invoice-edit', 'bixie/invoicemaker:app/bundle/invoicemaker-invoice.js', ['vue', 'editor']);
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
				<em>{{ 'Status' | trans }}:</em> <span>{{ getStatusText(invoice.status) }}</span>

			</div>
			<div data-uk-margin>

				<a class="uk-button uk-margin-small-right" :href="$url.route('admin/invoicemaker')">{{ invoice.id ?
					'Close' :
					'Cancel' | trans }}</a>
				<button class="uk-button uk-button-primary" type="submit">{{ 'Save' | trans }}</button>

			</div>
		</div>

		<div class="uk-grid pk-grid-large pk-width-sidebar-large" data-uk-grid-margin>
			<div class="pk-width-content">

				<iframe v-el:iframe src="<?=$iframe_src?>" frameborder="0" class="uk-responsive-width" width="800" height="1130"></iframe>

			</div>
			<div class="pk-width-sidebar">

				<div class="uk-form-row">
					<span class="uk-form-label">{{ 'Download PDF' | trans }}</span>
					<div class="uk-form-controls">
						<a :href="invoice.pdf_url" download><i class="uk-icon-external-link uk-margin-small-right"></i>
							{{ invoice.pdf_filename }}</a>
					</div>
				</div>

				<div v-if="invoice.pdf_file" class="uk-form-row">
					<span class="uk-form-label">{{ 'PDF file' | trans }}</span>
					<div class="uk-form-controls">
						<button type="button" class="uk-button uk-button-primary" @click="rerender">{{ 'Rerender PDF file' | trans }}</button>
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

			</div>
			
		</div>

	</form>

</div>

