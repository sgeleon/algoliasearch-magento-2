const observing = [];

require(
	[
		'jquery',
		'mage/translate',
	],
	function ($) {

		addDashboardWarnings();

		function addDashboardWarnings() {
			// rows
			var rowIds = [
				'#row_algoliasearch_instant_instant_facets_facets',
				'#row_algoliasearch_instant_instant_facets_max_values_per_facet'
			];

			var rowWarning = '<div class="algolia_dashboard_warning">';
			rowWarning += '<p>This setting is also available in the Algolia Dashboard. We advise you to manage it from this page, because saving Magento settings will override the Algolia settings.</p>';
			rowWarning += '</div>';

			for (var i=0; i < rowIds.length; i++) {
				var element = $(rowIds[i]);
				if (element.length > 0) {
					element.find('.value').prepend(rowWarning);
				}
			}

			// pages
			var pageIds = [
				'#algoliasearch_products_products',
				'#algoliasearch_categories_categories',
				'#algoliasearch_credentials_credentials',
				'#algoliasearch_extra_settings_extra_settings'
			];

			var pageWarning = '<div class="algolia_dashboard_warning algolia_dashboard_warning_page">';
			pageWarning += '<p>These settings are also available in the Algolia Dashboard. We advise you to manage it from this page, cause saving Magento settings will override the Algolia settings.</p>';
			pageWarning += '</div>';

			var pageWarningSynonyms = '<div class="algolia_dashboard_warning algolia_dashboard_warning_page">';
			pageWarningSynonyms += '<p>Configurations related to Synonyms have been removed from the Magento dashboard. We advise you to configure synonyms from the Algolia dashboard.</p>';
			pageWarningSynonyms += '</div>';

			for (var i=0; i < pageIds.length; i++) {
				var element = $(pageIds[i]);
				if (element.length > 0 && pageIds[i] != "#algoliasearch_credentials_credentials") {
					element.find('.comment').append(pageWarning);
				} else if (element.length > 0 && pageIds[i] == "#algoliasearch_credentials_credentials"){
				    element.find('.comment').append(pageWarningSynonyms);
				}
			}
		}

		if ($('#algoliasearch_instant_instant_facets_facets').length > 0) {
			var addButton = $('#algoliasearch_instant_instant_facets tfoot .action-add');
			addButton.on('click', function(){
				handleFacetQueryRules();
			});

			handleFacetQueryRules();
		}

		function handleFacetQueryRules() {
			var facets = $('#algoliasearch_instant_instant_facets_facets tbody tr');

			for (var i=0; i < facets.length; i++) {
				let rowId = $(facets[i]).attr('id');
				console.log("Row ID:", rowId);
				let searchableSelect = $('select[name="groups[instant_facets][fields][facets][value][' + rowId + '][searchable]"]');

				searchableSelect.on('change', function(){
					configQrFromSearchableSelect($(this));	
				});

				// setTimeout(() => { 
					// console.log("Invoke on delay");
					configQrFromSearchableSelect(searchableSelect) 
				// }, 100);
					
			}
		}

		function configQrFromSearchableSelect(searchableSelect) {
			var rowId = searchableSelect.parent().parent().attr('id');
			const qrSelectId = 'select[name="groups[instant_facets][fields][facets][value][' + rowId + '][create_rule]"]';

			if (!observing[qrSelectId]) {

				const targetNode = document.querySelector(qrSelectId);
				const config = { attributes: true, childList: false, subtree: false, attributeOldValue : true };
				// Callback function to execute when mutations are observed
				const callback = function (mutationsList, observer) {
					console.log("MutationObserver callback triggered.");
					console.trace();
					for (var mutation of mutationsList) {
						if (mutation.type === 'attributes') {
							console.log(
								'The ' + mutation.attributeName + ' attribute was modified: ',
								performance.now()
							);
						}
						console.log(mutation);
					}
				};
				const observer = new MutationObserver(callback);
				observer.observe(targetNode, config);

				observing[qrSelectId] = true;
			}
			
			// var searchableSelect = $('select[name="groups[instant_facets][fields][facets][value][' + rowId + '][searchable]"]');
			var qrSelect = $(qrSelectId);
			if (qrSelect.length > 0) {
				if (searchableSelect.val() == "2") {
					qrSelect.val('2');
					qrSelect.attr('disabled','disabled');
					qrSelect.attr('readonly', 'readonly');
					console.log("Time of disabling: ", performance.now());
				} else {
					qrSelect.removeAttr('disabled');
					qrSelect.removeAttr('readonly');
				}
			} else {
				$('#row_algoliasearch_instant_instant_facets_facets .algolia_block').hide();
			}
		}

	}
);	
