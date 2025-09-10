(function ($, moduleConfig, config) {

	function configSetDefault(config) {
		if (!config.hasOwnProperty('mapping')) {
			config.mapping = [];
		}

		if (!config.hasOwnProperty('scheduled_sync')) {
			config.scheduled_sync = {
				type: 'manual',
				recurrence: '',
			};
		}

		for (let i=0;i<config.mapping.length;i++) {
			if (!config.mapping[i].hasOwnProperty('options')) {
				config.mapping[i].options = {};
			}
		}

		if (!config.hasOwnProperty('notices')) {
			config.notices = {};
		}

		if (!config.hasOwnProperty('template')) {
			config.template = 'air_woo_sync_template';
		}

		if (!config.hasOwnProperty('mappingAuto')) {
			config.mappingAuto = {};
		}

		if (!config.hasOwnProperty('mappingInit')) {
			config.mappingInit = { "orders" : [] };
		}

		return config;
	}

	$( function(){
		const finalConfig = {
			// Current conf
			...configSetDefault(config),
			// Mandatory
			...{
				enable_link_to_another_record: 'yes',
			},

		};

		window.syncWoocommerceOrdersToAirtableRenderUIModule({
			...moduleConfig,
			id: 'orders-sync-to-airtable-for-woocommerce-exporter-wizard',
			module: 'exporter-wizard',
			fetchFn: window.syncWoocommerceOrdersToAirtableFetcherFactory(moduleConfig.fetchFn),
			i18n: wp.i18n,
			config: finalConfig,
			isOptionAvailable: (value) => {
				return wp.hooks.applyFilters('orders-sync-to-airtable-for-woocommerce.isOptionAvailable', true, value, { config: finalConfig });
			},
			onComplete: () => {
				console.log('wizard: Done')
			},
			onConfigUpdate: (config) => {
				console.log('onConfigUpdate', config)
			},

		})
	});

})(jQuery, window.syncWoocommerceOrdersToAirtableWizardModuleConfig, window.syncWoocommerceOrdersToAirtableWizardConfig)
