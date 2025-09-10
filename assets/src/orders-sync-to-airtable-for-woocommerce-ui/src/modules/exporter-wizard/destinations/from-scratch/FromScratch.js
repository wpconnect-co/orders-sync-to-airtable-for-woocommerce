import Heading from "air-wp-sync-ui/library/components/content/heading";
import {useContext, useEffect, useState} from "react";
import TranslationsContext from "../../../../utils/TranslationsContext";
import FormRow from "air-wp-sync-ui/library/components/form/form-row";
import Select from "air-wp-sync-ui/library/components/form/select";
import FetcherContext from "../../../../utils/FetcherContext";
import {fetchForm, FORM_STATE} from "../../../../utils/fetcher";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import CircleLoadingAnimation from "air-wp-sync-ui/library/components/graphics/circle-loading-animation";

export default function FromScratch({ accessToken, baseId, tableId, setFormState, onComplete, setBaseInfo }) {
	const { __ } = useContext(TranslationsContext);
	const fetchFn = useContext(FetcherContext);
	const [ bases, setBases ] = useState([]);
	const [ basesFormStatus, setBasesFormStatus ] = useState(FORM_STATE);
	const [ tables, setTables ] = useState([]);
	const [ tablesFormStatus, setTablesFormStatus ] = useState(FORM_STATE);

	useEffect(() => {
		setBasesFormStatus(FORM_STATE);
		setTablesFormStatus(FORM_STATE);
		const formData = new FormData();
		formData.set('accessToken', accessToken);
		fetchForm(fetchFn, 'get-airtable-bases', setBasesFormStatus, formData);
	}, [fetchFn, accessToken]);

	useEffect(() => {
		if (basesFormStatus.success) {
			setBases(basesFormStatus.data.bases.map((base) => ({
				value: base.id,
				label: base.name,
			})));
		}
	}, [basesFormStatus])

	useEffect(() => {
		if (!baseId) {
			return;
		}
		const formData = new FormData();
		formData.set('accessToken', accessToken);
		formData.set('baseId', baseId);
		fetchForm(fetchFn, 'get-airtable-tables', setTablesFormStatus, formData);

	}, [baseId, fetchFn, accessToken]);

	useEffect(() => {
		if (tablesFormStatus.success) {
			setTables(tablesFormStatus.data.tables.map((table) => ({
				value: table.id,
				label: table.name,
			})));
		}
	}, [tablesFormStatus])

	const cleanUpAndComplete = () => {
		setFormState((prevFormState) => ({
			...prevFormState,
			// Make sure we reset these props as well.
			mappingAuto: {},
			mappingInit: { "orders" : []},
			airtableFields: [],
		}));
		onComplete();
	};

	return <>
		<Heading level={2}>{ __('Set destination', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		<Spacer size={32} />
		<FormRow>
			{ basesFormStatus.success ? <Select
				label={ __('Pick your Airtable base', 'orders-sync-to-airtable-for-woocommerce') }
				placeholder={ __('Airtable base', 'orders-sync-to-airtable-for-woocommerce') }
				value={bases.find((option) => option.value === baseId)}
				options={bases.map((option) => ({...option}))}
				onChange={(newOption) => {
					setTablesFormStatus(FORM_STATE);
					setFormState((prevFormState) => ({
						...prevFormState,
						baseId: newOption ? newOption.value : '',
					}));
					setBaseInfo({
						baseName: newOption ? newOption.label : '',
						baseId: newOption ? newOption.value : '',
						baseURL: newOption ? 'https://airtable.com/' + newOption.value + '/' : '',
					});
				}}
			/> : displayState(basesFormStatus) }
		</FormRow>
		<Spacer size={32} />
		<FormRow>
			{ tablesFormStatus.success ? <Select
				label={ __('Pick the Airtable table', 'orders-sync-to-airtable-for-woocommerce') }
				placeholder={ __('Airtable table', 'orders-sync-to-airtable-for-woocommerce') }
				value={tables.find((option) => option.value === tableId)}
				options={tables.map((option) => ({...option}))}
				onChange={(newOption) => {
					setFormState((prevFormState) => ({
						...prevFormState,
						tableId: newOption ? newOption.value : ''
					}))
				}}
			/> : displayState(tablesFormStatus) }
		</FormRow>
		{
			baseId && tableId ?
				<>
					<Spacer size={32} />
					<ButtonIcon icon="arrow-right" type="button" onClick={ cleanUpAndComplete }>{ __('Next step', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
				</>
				: null

		}
	</>
}

function displayState(formStatus) {
	if (formStatus.isLoading) {
		return <CircleLoadingAnimation />;
	}
	return null;
}
