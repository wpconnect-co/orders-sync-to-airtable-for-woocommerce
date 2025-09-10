import Heading from "air-wp-sync-ui/library/components/content/heading";
import {useCallback, useContext, useEffect, useState} from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import Translate, {TranslateHTML} from "../../utils/Translate";
import FormRow from "air-wp-sync-ui/library/components/form/form-row";
import Select from "air-wp-sync-ui/library/components/form/select";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import ButtonGroup from "air-wp-sync-ui/library/components/form/button-group"
import Button from "air-wp-sync-ui/library/components/form/button"
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import {fetchForm, FORM_STATE} from "../../utils/fetcher";
import FetcherContext from "../../utils/FetcherContext";
import SyncProgress from "./SyncProgress";
import ButtonLinkIcon from "air-wp-sync-ui/library/components/content/button-link-icon";
export default function Sync({ formState, setFormState, scheduledSyncTypes }) {
	const { __ } = useContext(TranslationsContext);
	const fetchFn = useContext(FetcherContext);
	const [ triggerSyncFormStatus, setTriggerSyncFormStatus ] = useState(FORM_STATE);
	const [ saveFormStatus, setSaveFormStatus ] = useState(FORM_STATE);

	useEffect(() => {
		if (true === triggerSyncFormStatus.success) {
			setTriggerSyncFormStatus(FORM_STATE);
		} else if (false === triggerSyncFormStatus.success) {
			setFormState((prevFormState) => ({
				...prevFormState,
				connection_status: {
					...prevFormState.connection_status,
					status: triggerSyncFormStatus.error.status,
					errors: triggerSyncFormStatus.error.errors,
					logs_url: triggerSyncFormStatus.error.logs_url,
				}
			}));
			setTriggerSyncFormStatus(FORM_STATE);
		}
	}, [ triggerSyncFormStatus, setFormState ]);

	const startSync = useCallback(() => {
		const formData = new FormData();
		fetchForm(fetchFn, 'trigger-update-url', setTriggerSyncFormStatus, formData);

		setFormState((prevFormState) => ({
			...prevFormState,
			connection_status: {
				...prevFormState.connection_status,
				status: 'loading',
			}
		}))
	}, [ fetchFn, setFormState, setTriggerSyncFormStatus ]);

	const publish = useCallback(() => {
		setSaveFormStatus(FORM_STATE);
		const formData = new FormData();
		formData.set('settings', JSON.stringify(formState));
		return fetchForm(fetchFn, 'save-settings', setSaveFormStatus, formData);
	}, [ fetchFn, formState, setSaveFormStatus ]);

	const publishAndSyncHandler = useCallback(async function () {
		try {
			const result = await publish();
			if (result.success) {
				startSync();
				setSaveFormStatus(FORM_STATE);
			}
		} catch (e) {
			console.error(e);
			setSaveFormStatus({ ...FORM_STATE, success: false, error: e.message });
		}
	}, [publish, setSaveFormStatus, startSync]);

	const publishHandler = useCallback(async function () {
		try {
			await publish();
		} catch (e) {
			console.error(e);
			setSaveFormStatus({ ...FORM_STATE, success: false, error: e.message });
		}
	}, [publish, setSaveFormStatus]);

	const isLoading = triggerSyncFormStatus.isLoading || saveFormStatus.isLoading;
	if ('success' === formState.connection_status?.status) {
		return <SyncSuccess timeSpent={ formState.connection_status.time_spent } countProcessed={ formState.connection_status.count_processed } />;
	} else if ('error' === formState.connection_status?.status) {
		return <SyncFailed formState={ formState } isLoading={ isLoading } publishAndSyncHandler={ publishAndSyncHandler } />;
	} else if ('loading' === formState.connection_status?.status) {
		return <SyncProgress
			formState={ formState }
			setFormState={ setFormState }
			isInitialLoading={ triggerSyncFormStatus.isLoading }
		/>
	}

	return <>
		<Heading level={2}>{ __('Set the last step', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		<Spacer size={32} />

		<Heading level={3}>{ __('Sync options', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		<FormRow style={{
			maxWidth: '292px'
		}}>
			<FormRow>
				<Select
					label={ __('Choose your sync mode', 'orders-sync-to-airtable-for-woocommerce') }
					value={ scheduledSyncTypes.find((option) => option.value === formState.scheduled_sync.type) }
					options={ scheduledSyncTypes.map((option) => ({...option, isDisabled: !option.enabled})) }
					onChange={(newValue) => {
						setFormState((prevFormState) => ({
							...prevFormState,
							scheduled_sync: {
								...formState.scheduled_sync,
								type: newValue ? newValue.value : 'manual',
							}
						}));
					}}
				/>
			</FormRow>
		</FormRow>
		{
			'order_state_change' === formState.scheduled_sync?.type ?
				<>
					<Spacer size={16} />
					<Paragraph fontSize="xs">{ __('* Orders are automatically synchronized either when they are first created or whenever their status is updated', 'orders-sync-to-airtable-for-woocommerce') }</Paragraph>
				</>
				: null
		}
		<Spacer size={32} />
		{
			true === saveFormStatus.success ?
				<><PrebuiltCallout type="success">{ saveFormStatus.data }</PrebuiltCallout><Spacer size={32} /></>
				: null
		}
		{
			false === saveFormStatus.success ?
				<><PrebuiltCallout type="error">{ saveFormStatus.error }</PrebuiltCallout><Spacer size={32} /></>
				: null
		}
		<ButtonGroup>
			<ButtonIcon
				icon="verify"
				onClick={ publishAndSyncHandler }
				disabled={ isLoading }
			>{ __('Save and sync!', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
			<Button
				buttonType="secondary"
				onClick={ publishHandler }
				disabled={ isLoading }
			>{ __('Save modifications', 'orders-sync-to-airtable-for-woocommerce') }</Button>
		</ButtonGroup>
		{
			'order_state_change' === formState.scheduled_sync?.type ?
				<>
					<Spacer size={16} />
					<Paragraph fontSize="xs">{ __('Syncing is not instantly, it can takes up to 5 minutes to be updated on Airtable', 'orders-sync-to-airtable-for-woocommerce') }</Paragraph>
				</>
				: null
		}
	</>

}

export function SyncFailed({ formState, isLoading, publishAndSyncHandler }) {
	const { __ } = useContext(TranslationsContext);
	const errors = (formState.connection_status.errors ?? []);
	return <>
		<Heading level={2}>{ __('Oh no! export failed...', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		<Spacer size={24} />
		<PrebuiltCallout type="error">{ __('It seems that there are some errors during your export. You can check them out below.', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout>
		<Spacer size={32} />
		<PrebuiltCallout type="warning">
			<TranslateHTML
				string={({ __ }) => __('Please, <strong>note that the changes made to your settings have been saved.</strong>', 'orders-sync-to-airtable-for-woocommerce')}
				allowedHTMLTags={['strong']}
			/>
		</PrebuiltCallout>

		{
			errors.length > 0 ?
				<>
					<Spacer size={32} />
					<Paragraph color="error" weight="bold"><strong>{ __('Errors:', 'orders-sync-to-airtable-for-woocommerce') }</strong></Paragraph>
					{
						errors.map((errorMessage, index) => {
							return <Paragraph key={ index } color="error">{ errorMessage }</Paragraph>
						})
					}
				</>
				: null
		}
		<Spacer size={32} />
		<ButtonGroup>
			<ButtonIcon
				icon="verify"
				onClick={publishAndSyncHandler}
				disabled={ isLoading }
			>{ __('Restart the sync', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
			<ButtonLinkIcon
				buttonType="secondary"
				icon="download"
				iconPos="before"
				underlined={ false }
				href={ formState.connection_status.logs_url }
				download
			>{ __('Download logs', 'orders-sync-to-airtable-for-woocommerce') }</ButtonLinkIcon>
		</ButtonGroup>


	</>
}


export function SyncSuccess({ timeSpent, countProcessed = -1, countDeleted = -1 }) {
	const { __ } = useContext(TranslationsContext);
	return <>
		<Heading level={2}>{ __('Done! Your WooCommerce orders are now synced with Airtable.', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		{
			countProcessed > -1 && countDeleted > -1 ?
				<Paragraph color="primary-300">
					<Translate string={ ({ _n }) => (
						// translators: %d how many product processed.
						_n(
							'%d order processed',
							'%d orders processed',
							countProcessed,
							'orders-sync-to-airtable-for-woocommerce'
						)
					) } placeholders={ { '%d': countProcessed } } />
				</Paragraph>
				: null
		}
		<Spacer size={24} />
		<Paragraph weight="bold">
			<TranslateHTML
				/* translators: %1$s: time spent for synchronisation */
				string={({ __ }) => __('Sync finished in <strong>%1$s</strong> — now enjoy the <strong>time saved!</strong>', 'orders-sync-to-airtable-for-woocommerce') }
				allowedHTMLTags={['strong']}
				placeholders={ {
					'%1$s' : timeSpent
				} }
			/>
		</Paragraph>


	</>
}
