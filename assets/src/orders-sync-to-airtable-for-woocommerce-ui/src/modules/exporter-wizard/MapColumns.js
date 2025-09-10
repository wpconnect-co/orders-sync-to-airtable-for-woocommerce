import Heading from "air-wp-sync-ui/library/components/content/heading";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import MappingRowGroup from "air-wp-sync-ui/library/components/form/mapping-row-group";
import MappingRowTemplate from "air-wp-sync-ui/library/components/form/mapping-row-template";
import {Fragment, useContext, useEffect, useState, useRef, useMemo} from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import Button from "air-wp-sync-ui/library/components/form/button"
import {fetchForm, FORM_STATE} from "../../utils/fetcher";
import FetcherContext from "../../utils/FetcherContext";
import {defaultMappingOptionsToWordPressField, getAirtableFieldById} from "air-wp-sync-ui/library/models/mapping/helpers.ts";
import MappingRowReversed from "air-wp-sync-ui/library/components/form/mapping-row-reversed";
import MappingManagerReversed from "air-wp-sync-ui/library/models/mapping/MappingManagerReversed.ts"
import Sortable from 'sortablejs';
import CircleLoadingAnimation from "air-wp-sync-ui/library/components/graphics/circle-loading-animation";

export default function MapColumns({ productTypes, defaultMappingOptions, isOptionAvailable, formState, setFormState, onComplete }) {
	return <EditMapping { ...{ formState, setFormState, onComplete, defaultMappingOptions, productTypes, isOptionAvailable } } />;
}

const checkTemplate = async ({ formState, mapping, fetchFn, setMappingFormStatus }) => {
	const formData = new FormData();
	formData.append('accessToken', formState.accessToken);
	formData.append('baseId', formState.baseId);
	const fields = getMappingInitFromFormState(formState.mappingInit).reduce((carry, group) => {
		carry = carry.concat(group.mappings.map((mapping) => {
			return mapping.wordpress;
		}));
		return carry;
	}, []);
	const mappedWordPressFields = mapping.map((m) => m.wordpress);
	fields.forEach((field) => {
		if (mappedWordPressFields.indexOf(field) > -1) {
			formData.append('fields[]', field);
		}
	})

	return fetchForm(fetchFn, 'check-template', setMappingFormStatus, formData);
}

export const EditMapping = ({ formState, setFormState, onComplete, defaultMappingOptions, isOptionAvailable }) => {
	const { __ } = useContext(TranslationsContext);
	const fetchFn = useContext(FetcherContext);
	const [airtableFields, setAirtableFields] = useState([]);
	const [airtableFieldsFormStatus, setAirtableFieldsFormStatus ] = useState(FORM_STATE);
	const [mappingTemplate, setMappingTemplate] = useState(getMappingInitFromFormState(formState.mappingInit));
	const [mapping, setMapping] = useState(formState.mapping);
	const [mappingFormStatus, setMappingFormStatus] = useState(FORM_STATE);
	useEffect(() => {
		setMappingFormStatus(FORM_STATE);
		setMappingTemplate(getMappingInitFromFormState(formState.mappingInit));
	}, [formState.mappingInit]);
	useEffect(() => {
		const formData = new FormData();
		formData.set('accessToken', formState.accessToken);
		formData.set('baseId', formState.baseId);
		formData.set('tableId', formState.tableId);
		setAirtableFieldsFormStatus(FORM_STATE);
		fetchForm(fetchFn, 'get-airtable-table-fields', setAirtableFieldsFormStatus, formData);
	}, [formState.accessToken, formState.baseId, formState.tableId, fetchFn, formState.template]);
	useEffect(() => {
		if (airtableFieldsFormStatus.success) {
			setAirtableFields(airtableFieldsFormStatus.data.fields)
		}
	}, [airtableFieldsFormStatus])

	const inputRef = useRef(null);

	const wordPressFields = defaultMappingOptionsToWordPressField(defaultMappingOptions);
	const texts = {
		custom_field: __('Custom Field', 'orders-sync-to-airtable-for-woocommerce'),
		airtable_field: __('Airtable Field', 'orders-sync-to-airtable-for-woocommerce'),
		fields: __('Fields', 'orders-sync-to-airtable-for-woocommerce'),
		sort: __('Sort', 'orders-sync-to-airtable-for-woocommerce'),
		remove: __('Remove', 'orders-sync-to-airtable-for-woocommerce'),
		assigned_to: __('Assigned to', 'orders-sync-to-airtable-for-woocommerce'),
		wordpress_field_placeholder: __('WordPress field', 'orders-sync-to-airtable-for-woocommerce'),
	};
	const flatAutoMappingTemplate = expandMappingAuto(formState.mappingAuto, airtableFields);

	const mappingManager = useMemo(() => {
		// Tag mapping with the prop "added" to true, to make the difference between mapping from the initial template and fields added by the user.
		const setMappingWithAddedInfo = function (newMapping) {
			const mappedWordPressFields = mapping.map((m) => m.wordpress);
			if (newMapping.length === 0) {
				setMapping([{}]);
			} else {
				setMapping(newMapping.map((m) => {
					if (mappedWordPressFields.indexOf(m.wordpress) === -1) {
						return {
							...m,
							added: true,
						};
					}
					return m;
				}));
			}
		};
		const isWordPressOptionEnabled = (option, wordPressFieldsSelected, airtableFieldsSelected) => {
			// A WordPress option can be selected more than once if we are not using the template "air_woo_sync_template" here so just check if it's enabled.
			return option.enabled && (formState.template !== 'air_woo_sync_template' || wordPressFieldsSelected.indexOf(option.value) === -1);
		};
		const isAirtableOptionEnabled = (option, wordPressFieldsSelected, airtableFieldsSelected) => {
			// An Airtable option can only be selected once.
			return airtableFieldsSelected.indexOf(option.id) === -1;
		};
		return new MappingManagerReversed(
			mapping,
			setMappingWithAddedInfo,
			airtableFields,
			wordPressFields,
			defaultMappingOptions,
			isOptionAvailable,
			flatAutoMappingTemplate,
			isWordPressOptionEnabled,
			isAirtableOptionEnabled
		);
	}, [mapping, setMapping, airtableFields, wordPressFields, defaultMappingOptions, isOptionAvailable, flatAutoMappingTemplate, formState.template]);

	const filteredMapping = useMemo(() => {
		return filterMapping(mappingManager.mapping, airtableFields);
	}, [mappingManager, airtableFields]);

	useEffect(() => {
		if (!inputRef.current) {
			return ;
		}
		const sortableInstance = Sortable.create(inputRef.current, {
			handle: '.airwpsync-c-mapping-row-reversed__btn-sort, .airwpsync-c-mapping-row-template__btn-sort',
			onUpdate: (evt) => {
				mappingManager.moveMappingRow(evt.oldIndex ?? 0, evt.newIndex ?? 0);
			}
		});

		return () => {
			sortableInstance.destroy();
		};
	}, [ mapping, mappingManager ]);
	const wordPressFieldsSelectedFromTemplate = filteredMapping.map(( fieldMapping) => {
		return !fieldMapping.added ? fieldMapping.wordpress : false;
	}, []).filter((m) => m !== false);

	let wordPressFieldsInTemplate = [];

	const verify = async (event) => {
		event.preventDefault();

		let errors = false;
		setMappingFormStatus({
			...FORM_STATE,
			isLoading: true
		});
		const currentMapping = filteredMapping;
		currentMapping.forEach((m) => {
			if (!m.wordpress || !m.airtable) {
				if (!errors) {
					errors = { orders: { fields_mapping: {} } };
				}
				errors.orders.fields_mapping[m.wordpress] = __('Both WooCommerce and Airtable fields should be selected', 'orders-sync-to-airtable-for-woocommerce');
			}
		});

		if (!errors && currentMapping.length === 0) {
			errors = { orders: { fields_mapping: {} } };
			errors.orders.fields_mapping[''] = __('At least one field should be mapped', 'orders-sync-to-airtable-for-woocommerce');
			setMapping([{wordpress: ''}]);
		}

		if (errors) {
			setMappingFormStatus({
				...FORM_STATE,
				error: errors,
				success: false,
			});
			return false;
		}

		if (formState.template !== 'air_woo_sync_template') {
			setMappingFormStatus(FORM_STATE);
			setFormState((prevFormState) => ({
				...prevFormState,
				mapping: currentMapping,
			}));
			onComplete();
			return;
		}
		const response = await checkTemplate({ formState, mapping, fetchFn, setMappingFormStatus });
		if (response.success) {
			setFormState((prevFormState) => ({
				...prevFormState,
				mapping: currentMapping
			}));
			onComplete();
		}

		return false;
	}
	let intro = null;
	if (false === mappingFormStatus.success) {
		intro = <>
			<PrebuiltCallout type="error">{ __('Please, check error messages below.', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout>
			<Spacer size={10} />
			{ intro }
		</>;
	}

	let actionButton = <ButtonIcon icon="arrow-right">{ __('Next step', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>;
	if (mappingFormStatus.isLoading) {
		actionButton = <ButtonIcon icon="circle-loading" disabled={ true } iconPos="before">{ __('Verifying...', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
	} else if (true === mappingFormStatus.success) {
		actionButton = <ButtonIcon icon="arrow-right" type="button" onClick={() => {
			setFormState((prevFormState) => ({
				...prevFormState,
				mapping
			}));
			onComplete();
		}}>{ __('Next step', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>;
	} else if (false === mappingFormStatus.success) {
		const errors = mappingFormStatus.error?.errors;
		actionButton = <>
			{
				Object.keys(errors ?? {}).map((errorKey) => {
					return <Fragment key={errorKey}><PrebuiltCallout type="error">{ errors[errorKey] }</PrebuiltCallout><Spacer size={10} /></Fragment>;
				})
			}
			<PrebuiltCallout type="error">{ __('Please, check error messages above.', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout>
			<Spacer size={32} />
			<ButtonIcon icon="verify">{ __('Restart the verification', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
		</>;
	}
	const mappingIndex = filteredMapping.reduce((carry, m, index) => {
		carry[m.wordpress] = index;
		return carry;
	}, {});

	const heading = formState.template === 'air_woo_sync_template' ?
		<Heading level={2}>{ __('Check the field mapping') }</Heading>
		: <Heading level={2}>{ __('Check the field mapping') }</Heading>;
	if (airtableFieldsFormStatus.isLoading || airtableFieldsFormStatus.error) {
		return <>
			{ heading }
			<Spacer size={32}/>
			{
				airtableFieldsFormStatus.error ?
					<PrebuiltCallout type="error">{ airtableFieldsFormStatus.error }</PrebuiltCallout>
					: <CircleLoadingAnimation />
			}
		</>
	}

	return <form onSubmit={verify}>
		{
			heading
		}


		{ intro }

		<Spacer size={32}/>
		<table className="form-table">
			<thead>
			<tr>
				<th colSpan={2} style={{
					padding: "10px 16px 0",
					fontSize: "1.2rem"
				}}>{__('WooCommerce fields', 'orders-sync-to-airtable-for-woocommerce')}</th>
				<th style={{
					padding: "10px 16px 0",
					fontSize: "1.2rem"
				}}>{__('Airtable fields', 'orders-sync-to-airtable-for-woocommerce')}</th>
			</tr>
			</thead>
			{
				filterMappingTemplate(mappingTemplate, wordPressFieldsSelectedFromTemplate).map((group) => {
					return <MappingRowGroup key={group.groupName} label={group.groupName} texts={texts}>
						{
							group.mappings.map((mappingRow) => {
								const hasError = mappingFormStatus.error?.orders?.fields_mapping[mappingRow.wordpress];
								let mappingRowStatus = 'idle';
								if (false === mappingFormStatus.success) {
									mappingRowStatus = hasError ? 'invalid' : 'valid';
								}
								wordPressFieldsInTemplate.push(mappingRow.wordpress);
								const index = mappingIndex[mappingRow.wordpress];

								return <MappingRowTemplate
									key={filteredMapping[index].key}
									index={ index }
									status={mappingRowStatus}
									readOnly={mappingRow.readonly}
									expectedAirtableFieldName={mappingRow.airtableFieldName}
									texts={texts}
									wordPressFieldId={mappingRow.wordpress}
									mappingManager={mappingManager}
									errorMessage={ hasError ?? undefined}
								/>
							})
						}
					</MappingRowGroup>
				})
			}
			<MappingRowGroup key="added-fields" label={ __('More fields', 'orders-sync-to-airtable-for-woocommerce') } ref={ inputRef }>
				{
					filteredMapping.map((mappingRow, index) => {
						if (wordPressFieldsInTemplate.indexOf(mappingRow.wordpress) > -1) {
							return null;
						}

						const hasError = mappingFormStatus.error?.orders?.fields_mapping[mappingRow.wordpress];

						if (flatAutoMappingTemplate[mappingRow.wordpress]) {
							return <MappingRowTemplate
								key={index}
								sortable={true}
								status={ hasError ? 'error' : 'idle' }
								readOnly={flatAutoMappingTemplate[mappingRow.wordpress].readonly}
								expectedAirtableFieldName={flatAutoMappingTemplate[mappingRow.wordpress].airtableFieldName}
								texts={texts}
								wordPressFieldId={mappingRow.wordpress}
								mappingManager={mappingManager}
								errorMessage={ hasError ?? undefined }
								index={index}
							/>
						}
						return <MappingRowReversed
							key={mappingRow.key}
							texts={texts}
							index={index}
							airtableFieldId={mappingRow.airtable}
							wordPressFieldId={mappingRow.wordpress}
							fieldOptions={mappingRow.options}
							mappingManager={mappingManager}
							error={ hasError ?? undefined }
						/>
					})
				}
			</MappingRowGroup>
			<tfoot>
				<tr>
					<td colSpan={4}>
						<Button
							type="button"
							buttonType="secondary"
							onClick={() => {
								mappingManager.addMappingRow();
							}}
						>{ __('+ Add Field', 'orders-sync-to-airtable-for-woocommerce') }</Button>
					</td>
				</tr>
			</tfoot>
		</table>
		<Spacer size={32}/>
		{
			actionButton
		}
	</form>;
};

const getMappingInitFromFormState = (mappingInit) => {
	return mappingInit?.orders ?? [];
};

const filterMapping = (mapping, airtableFields) => {
	return mapping.filter((mappingRow) => {
		return !mappingRow.airtable || getAirtableFieldById(mappingRow.airtable, airtableFields);
	});
}

const filterMappingTemplate = (mappingTemplate, wordPressFieldsSelected) => {
	return mappingTemplate.reduce((carry, group) => {
		const mappings = group.mappings.filter((mappingRow) => {
			return wordPressFieldsSelected.indexOf(mappingRow.wordpress) > -1;
		});
		if (mappings.length === 0) {
			return carry;
		}
		carry.push({
			...group,
			mappings
		});
		return carry;
	}, []);
}

function expandMappingAuto(mappingAuto, airtableFields) {
	const mappingAutoExpanded = {};
	Object.keys(mappingAuto).forEach((wordPressFieldId) => {
		const airtableField = getAirtableFieldById(mappingAuto[wordPressFieldId], airtableFields);
		if (airtableField) {
			mappingAutoExpanded[wordPressFieldId] = {
				wordpress: wordPressFieldId,
				airtable: airtableField.id,
				airtableFieldName: airtableField.name,
			};
		}
	});
	return mappingAutoExpanded;
}
