<?php $view->script('invoices-invoicemaker', 'bixie/invoicemaker:app/bundle/invoicemaker-invoices.js', ['vue']) ?>

<div id="invoicemaker-invoices" class="uk-text uk-text-horizontal" v-cloak>

	<div class="uk-margin uk-flex uk-flex-space-between uk-flex-wrap" data-uk-margin>
		<div class="uk-flex uk-flex-middle uk-flex-wrap" data-uk-margin>

			<h2 class="uk-margin-remove">{{ 'Invoices' | trans }}</h2>

			<div class="uk-margin-left" v-show="selected.length">
				<ul class="uk-subnav pk-subnav-icon">
					<li><a class="pk-icon-delete pk-icon-hover" :title="'Delete' | trans"
						   data-uk-tooltip="{delay: 500}" @click.prevent="removeInvoices"
						   v-confirm="'Delete invoice?' | trans"></a>
					</li>
				</ul>
			</div>

		</div>
		<div class="uk-position-relative" data-uk-margin>

			<div>
				<a class="uk-button uk-button-primary" :href="$url.route('admin/invoicemaker/invoice/edit')">
					{{ 'Add invoice' | trans }}</a>
			</div>

		</div>
	</div>

	<div class="uk-overflow-container">
		<table class="uk-table uk-table-hover uk-table-middle">
			<thead>
			<tr>
				<th class="pk-table-width-minimum"><input type="checkbox" v-check-all:selected.literal="input[name=id]" number></th>
				<th class="pk-table-width-100">
					<input-filter :title="$trans('Template')" :value.sync="config.filter.template" :options="templateOptions"></input-filter>
				</th>
				<th class="pk-table-width-100">
					<input-filter :title="$trans('Group')" :value.sync="config.filter.invoice_group" :options="groupOptions"></input-filter>
				</th>
				<th class="pk-table-min-width-100" v-order:invoice_number="config.filter.order">{{ 'Invoice #' | trans }}</th>
				<th class="pk-table-min-width-200">{{ 'Debtor name' | trans }}</th>
				<th class="" v-order:created="config.filter.order">{{ 'Created' | trans }}</th>
				<th class="" v-order:amount="config.filter.order">{{ 'Amount' | trans }}</th>
				<th class="pk-table-min-width-200">{{ 'Download' | trans }}</th>
			</tr>
			</thead>
			<tbody>
			<tr class="check-item" v-for="invoice in invoices" :class="{'uk-active': active(invoice)}">
				<td><input type="checkbox" name="id" value="{{ invoice.id }}" number></td>
				<td>
					<em>{{ invoice.template }}</em>
				</td>
				<td>
					{{ invoice.invoice_group }}
				</td>
				<td>
					<a :href="$url.route('admin/invoicemaker/invoice/edit', { id: invoice.id })">{{ invoice.invoice_number }}</a><br/>
				</td>
				<td>
					{{ invoice.debtor.name }}
				</td>
				<td>
					{{ invoice.created | date 'shortDate' }}
				</td>
				<td>
					{{ invoice.amount }}
				</td>
				<td>
					<a :href="invoice.pdf_url" download><i class="uk-icon-download uk-margin-small-right"></i>
						{{ invoice.pdf_filename }}</a>
				</td>
			</tr>
			</tbody>
		</table>
	</div>

	<h3 class="uk-h1 uk-text-muted uk-text-center" v-show="invoices && !invoices.length">{{ 'No invoices found.' | trans
		}}</h3>

	<v-pagination :page.sync="config.page" :pages="pages" v-show="pages > 1"></v-pagination>

</div>
