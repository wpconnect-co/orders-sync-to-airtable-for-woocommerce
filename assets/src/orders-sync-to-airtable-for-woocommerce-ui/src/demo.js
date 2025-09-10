import {
	airtableFields,
	defaultMappingOptions,
	isOptionAvailable,
	mappingInit,
	formOptions,
	mappingAuto,
} from "./resources/demo-data";

export default function demo (renderModule) {
	const i18n = {
		__: string => string,
		_n: (string, strings, count) => (count > 1 ? strings : string)
	};
	let ratio = 0.1;
	const baseInfo = {
		baseName: 'Orders Sync to Airtable for WooCommerce Template',
		baseId: 'appBase1',
		baseURL: 'https://airtable.com/',
		templateURL: 'https://airtable.com/appazTxkrHLhuAAVu/shrCMCfCoDExXhSy0',
		templateVersion: '1.0.0',
	};
	const defaultWizardProps = {
		i18n,
		module: 'exporter-wizard',
		buildPathUri: '/static',
		onConfigUpdate: (config) => {
			console.log('config updated', config)
		},
		publish: ({ connection, ...config })=> {
			return new Promise((resolve, reject) => {
				setTimeout(() => {
					resolve({  });
				}, 1000)
			})
		},
		config: {
			template: 'air_woo_sync_template',
			mapping: [{
				"wordpress": "order::order_number",
				"airtable": "fldx9NuchslbT2SgY",
			}],
			mappingAuto: {},
			mappingInit: { "orders" : []},
			scheduled_sync: {
				type: 'manual'
			},
			connection_status: {
				logs_url: '/fake-log-file.log',
				last_updated_formatted: 'June 17, 2025 1:54 pm',
			}
		},
		templateProps: {
			air_woo_sync_template: {
				templateURL: 'https://airtable.com/appazTxkrHLhuAAVu/shrCMCfCoDExXhSy0',
				latestTemplateVersion: '1.0.0',
			}
		},
		defaultMappingOptions,
		isOptionAvailable,
		formOptions,
		fetchFn: (key) => {
			return new Promise((resolve, reject) => {
				setTimeout(function () {
					console.log(key)
					if ('check-base-template' === key) {
						resolve({ success: true, data: {
							baseName: 'WooCommerce to Airtable Template',
							baseURL: 'https://airtable.com/',
							message: 'Your new Airtable base has been successfully created!',
							templateVersion: '0.2.0',
							baseId: 'appBase1',
							tableId: 'tblTable1',
							mappingInit,
							mappingAuto,
						} });
					} else if ( 'disconnect-base' === key ) {
						resolve({ success: true } );
					} else if ( 'get-airtable-bases' === key ) {
						resolve({ success: true, data: {
							bases: [
								{ id: "appBase1", name: "Custom Base for Orders" },
								{ id: "appBase2", name: "Other Base" }
							]
						} } );
					} else if ( 'get-airtable-tables' === key ) {
						resolve({
							success: true, data: {
								tables: [
									{id: "tblTable1", name: "Orders"},
									{id: "tblTable2", name: "Other Table"}
								]
							}
						});
					 } else if ( 'get-airtable-table-fields' === key ) {
						resolve({
							success: true, data: {
								fields: airtableFields
							}
						});
					 } else if ('trigger-update-url' === key) {
						ratio = 0.1
						resolve({ success: true });
					}  else if ('check-sync-progress' === key) {
						ratio = Math.min(1, ratio + 0.2);
						resolve({
							success: true,
							data: {
								ratio,
								remaining_minutes: Math.max(1, Math.floor(8 - 8 * ratio)),
								time_spent: '7min22s',
								count_processed: 10,
								count_deleted: 1,
								status: 'success',
								last_updated_formatted: 'June 17, 2025 1:54 pm',
								logs_url: '/fake-log-file.log',
							}
						});
						if (ratio >= 1) {
							ratio = 0
						}
					} else if ('cancel-sync' === key) {
						resolve({ success: true, });
					} else {
						resolve({ success: true })
					}
				}, 1000)
			});
		}
	};
	const runModuleHandler = (id, callback) => {
		document.querySelector('[href="#' + id + '"]').addEventListener('click', (e) => {
			callback({ id })
		})
	}



	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-access-token-will-fail', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			fetchFn: () => {
				return new Promise((resolve, reject) => {
					setTimeout(() => {
						resolve({ success: false, data: 'Wrong access token' })
					}, 1000)
				})
			},
			id
		});
	});


	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				mapping: [],
				accessToken: 'valid',
				connection_status: {
					currentStepKey: 'destination',
				}
			}
		});
	});
	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template-synchronized', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				mapping: [],
				accessToken: 'valid',
				connection_status: {
					currentStepKey: 'destination',
					baseInfo: {
						...baseInfo,
						templateVersion: '0.9.0'
					},
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
			}
		});
	});


	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template-check-base-check-fail', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				connection_status: {
					currentStepKey: 'destination',
				}
			},
			fetchFn: (key) => {
				return new Promise((resolve, reject) => {
					setTimeout(function () {
						if ('check-base-template' === key) {
							resolve({ success: false, data: 'base not found', code: 404 });
						}
					}, 3000)
				});
			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template-check-existing-base-check-fail', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				connection_status: {
					currentStepKey: 'destination',
					baseInfo: {
						status: 'base-not-found'
					},
				}
			},
			fetchFn: (key) => {
				return new Promise((resolve, reject) => {
					setTimeout(function () {
						if ('check-base-template' === key) {
							resolve( { success: false, data: 'A new base is available but it‘s not the one you have previously connected.', code: 400})
						} else if ( 'disconnect-base' === key ) {
							resolve({ success: true } );
						}
					}, 3000)
				});
			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template-mapping', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				connection_status: {
					currentStepKey: 'map_columns',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mappingInit,
				mappingAuto,
			},
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template-mapping-loading-airtable-fields-will-fail', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				connection_status: {
					currentStepKey: 'map_columns',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mappingInit,
				mappingAuto,
			},
			fetchFn: (key) => {
				return new Promise((resolve, reject) => {
					setTimeout(() => {
						if (key === 'get-airtable-table-fields') {
							resolve({ success: false, data: 'Can’t load Airtable fields, please double check your access token.' })
						}
					}, 1000)
				});
			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-air-woo-sync-template-mapping-check-fail', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'air_woo_sync_template',
				connection_status: {
					currentStepKey: 'map_columns',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mappingInit,
				mappingAuto,

			},
			fetchFn: (key) => {
				if (key === 'check-template') {
					return new Promise((resolve, reject) => {
						setTimeout(() => {

							resolve({
								success: false,
								data: {
									products: {
										fields_mapping: {
											'product::name': 'The field "Order #" could not be found in the table "Orders".'
										}
									},
									errors: {
										missing_table: "1 missing table(s): Orders.",
										template_field_not_found: "A field is missing in the template."
									}
								}
							})

						}, 1000);
					});
				} else {
					return defaultWizardProps.fetchFn(key)
				}
			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-from-scratch-template', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				accessToken: 'valid',
				connection_status: {
					currentStepKey: 'destination',
				},
				template: 'from_scratch',
				mapping: [{}],
			},
			fetchFn: (key) => {
				if ( 'get-airtable-table-fields' === key ) {
					return new Promise((resolve) => {
						setTimeout(function () {
							resolve({
								success: true, data: {
									// Exclude "Status" so we can mock different table.
									fields: airtableFields.filter((field) => {
										return field.name !== 'Status'
									})
								}
							});
						}, 1000);
					});
				} else {
					return defaultWizardProps.fetchFn(key);
				}
			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-from-scratch-template-sync', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'from_scratch',
				connection_status: {
					currentStepKey: 'sync',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mapping: [{
					"wordpress": "order::order_number",
					"airtable": "fldx9NuchslbT2SgY",
				}],
			},
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-from-scratch-template-sync-with-as-pending-actions', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'from_scratch',
				connection_status: {
					currentStepKey: 'sync',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mapping: [{
					"wordpress": "order::order_number",
					"airtable": "fldx9NuchslbT2SgY",
				}],
			},
			fetchFn: (key) => {
				if ('check-sync-progress' === key) {
					return new Promise((resolve) => {
						setTimeout(function () {
							ratio = Math.min(1, ratio + 0.2);
							resolve({
								success: true,
								data: {
									ratio,
									remaining_minutes: Math.max(1, Math.floor(8 - 8 * ratio)),
									time_spent: '7min22s',
									count_processed: 10,
									count_deleted: 1,
									status: 'success',
									as_other_pending_actions: 20,
								}
							});
							if (ratio >= 1) {
								ratio = 0
							}
							}, 1000)
						});
				} else {
					return defaultWizardProps.fetchFn(key)
				}

			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-from-scratch-template-sync-will-fail', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'from_scratch',
				connection_status: {
					currentStepKey: 'sync',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mapping: [{
					"wordpress": "order::order_number",
					"airtable": "fldx9NuchslbT2SgY",
				}],
			},
			fetchFn: (key) => {
				if ('trigger-update-url' === key) {
					return new Promise((resolve) => {
						setTimeout(function () {
							resolve({
								success: false,
								data: {
									status: 'error',
									errors: [
										'Airtable API is down'
									],
									logs_url: '/fake-log-file.log',
								}
							});
						}, 1000)
					});
				} else {
					return defaultWizardProps.fetchFn(key)
				}

			}
		});
	});

	runModuleHandler('orders-sync-to-airtable-for-woocommerce-exporter-wizard-from-scratch-template-sync-with-error', ({ id }) => {
		renderModule({
			...defaultWizardProps,
			id,
			config: {
				...defaultWizardProps.config,
				template: 'from_scratch',
				connection_status: {
					currentStepKey: 'sync',
					baseInfo,
				},
				baseId: 'appBase1',
				tableId: 'tblTable1',
				mapping: [{
					"wordpress": "order::order_number",
					"airtable": "fldx9NuchslbT2SgY",
				}],
			},
			fetchFn: (key) => {
				if ('check-sync-progress' === key) {
					return new Promise((resolve) => {
						setTimeout(function () {
							ratio = Math.min(1, ratio + 0.1);
							if (ratio < 0.5) {
								resolve({
									success: true,
									data: {
										ratio,
										remaining_minutes: Math.max(1, Math.floor(8 - 8 * ratio)),
										time_spent: '7min22s',
										count_processed: 10,
										count_deleted: 1,
										status: 'success'
									}
								})
							} else  {
								resolve({
									success: false,
									data: {
										status: 'error',
										errors: [
											'The field Order # cant’t be found'
										],
										logs_url: '/fake-log-file.log',
									}
								})
								ratio = 0.1
							}
						}, 1000)
					});
				} else {
					return defaultWizardProps.fetchFn(key)
				}

			}
		});
	});


	if (document.location.hash) {
		document.querySelector('[href="'+document.location.hash+'"]').click();
	}


}
