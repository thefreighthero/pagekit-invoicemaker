<?php $view->script('invoicemaker-settings', 'bixie/invoicemaker:app/bundle/invoicemaker-settings.js', ['vue']) ?>

<div id="invoicemaker-settings" class="uk-form">


	<div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
		<div data-uk-margin>

			<h2 class="uk-margin-remove">{{ 'Invoicemaker Settings' | trans }}</h2>

		</div>
		<div data-uk-margin>

			<button class="uk-button uk-button-primary" @click="save">{{ 'Save' | trans }}</button>

		</div>
	</div>

	<div class="uk-grid uk-grid-width-1-2 uk-form-horizontal" data-uk-grid-margin="">
		<div>
			<div class="uk-form-row">
				<span class="uk-form-label">{{ 'PDF files' | trans }}</span>
				<div class="uk-form-controls">
					<label for="text-save_pdfs" class="uk-form-label">
						<input id="text-save_pdfs" type="checkbox" name="save_pdfs"
							   v-model="config.save_pdfs"/> {{ 'Store PDFs on server' | trans }}
					</label>
				</div>
			</div>

			<div v-show="config.save_pdfs" class="uk-form-row">
				<label for="text-pdf_path" class="uk-form-label">{{ 'PDF path' | trans }}</label>
				<div class="uk-form-controls">
					<input id="text-pdf_path" type="text" name="pdf_path" class="uk-form-width-large" v-model="config.pdf_path"/>
				</div>
			</div>

			<h3>{{ 'Invoice groups' | trans }}</h3>

			<ul class="uk-list uk-list-line">
				<li v-for="group in config.invoice_groups">
					<a v-show="config.invoice_groups.length > 1" class="pk-icon-delete pk-icon-hover uk-float-right"
					   @click="config.invoice_groups.$remove(group)"
					   :title="'Delete' | trans" data-uk-tooltip="{delay: 500}"></a>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Name' | trans }}</label>
						<div class="uk-form-controls">
							<input type="text" class="uk-form-width-medium" v-model="group.name"/>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Format invoice number' | trans }}</label>
						<div class="uk-form-controls">
							<input type="text" class="uk-form-width-large" v-model="group.format"/>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Number of digits' | trans }}</label>
						<div class="uk-form-controls">
							<input type="number" class="uk-form-width-small uk-text-right" v-model="group.digits" min="2" max="8" number/>
						</div>
					</div>
				</li>
			</ul>
			<button class="uk-button" @click="addGroup">{{ 'Add group' | trans }}</button>

		</div>
		<div>

			<h3>{{ 'Templates' | trans }}</h3>

			<ul class="uk-list uk-list-line">
				<li v-for="template in config.templates">
					<a v-show="config.templates.length > 1" class="pk-icon-delete pk-icon-hover uk-float-right"
					   @click="config.templates.$remove(template)"
					   :title="'Delete' | trans" data-uk-tooltip="{delay: 500}"></a>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Name' | trans }}</label>
						<div class="uk-form-controls">
							<input type="text" class="uk-form-width-medium" v-model="template.name"/>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'PDF template' | trans }}</label>
						<div class="uk-form-controls">
							<select v-model="template.pdf_template" class="uk-form-width-medium">
								<option v-for="pdf_template in pdf_templates" :value="pdf_template">{{ pdf_template }}</option>
							</select>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Title' | trans }}</label>
						<div class="uk-form-controls">
							<input type="text" class="uk-form-width-large" v-model="template.title"/>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Credit title' | trans }}</label>
						<div class="uk-form-controls">
							<input type="text" class="uk-form-width-large" v-model="template.credit_title"/>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Creditor address' | trans }}</label>
						<div class="uk-form-controls">
							<textarea class="uk-form-width-large" v-model="template.creditor_address" rows="5" cols="40"></textarea>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'Subline' | trans }}</label>
						<div class="uk-form-controls">
							<textarea class="uk-form-width-large" v-model="template.subline" rows="2" cols="40"></textarea>
						</div>
					</div>
					<div class="uk-form-row">
						<label class="uk-form-label">{{ 'PDF background image' | trans }}</label>
						<div class="uk-form-controls">
							<input-image :source.sync="template.params.pdf_background" class="pk-image-max-height"></input-image>
						</div>
					</div>

				</li>
			</ul>
			<button class="uk-button" @click="addTemplate">{{ 'Add template' | trans }}</button>

		</div>




	</div>

</div>
