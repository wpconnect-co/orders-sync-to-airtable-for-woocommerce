import {useCallback, useContext, useEffect, useState} from "react";
import TranslationsContext from "../../utils/TranslationsContext";

import MapColumns from "./MapColumns";
import Sync from "./Sync";

import Heading from "air-wp-sync-ui/library/components/content/heading";
import Panel from "air-wp-sync-ui/library/components/layout/panel";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import Steps, {getNextKey, getPrevKey} from "air-wp-sync-ui/library/components/layout/steps";
import StepsIndex from "air-wp-sync-ui/library/components/content/steps-index";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import Welcome from "./Welcome";
import AccessTokenForm from "./AccessTokenForm";
import ChooseATemplate from "./ChooseATemplate";
import Destination from "./Destination";
import ConnectionStatus from "./ConnectionStatus";


const stepKeys = [ 'authorization', 'template', 'destination', 'map_columns', 'sync' ];

export default function ConnectionWizard({ config = {}, templateProps, formOptions, defaultMappingOptions, isOptionAvailable, onConfigUpdate }) {
	const { __ } = useContext(TranslationsContext);
	const [formState, setFormState] = useState(config);
	const [showWelcome, setShowWelcome] = useState(!formState.connection_status?.currentStepKey);
	useEffect(() => {
		onConfigUpdate(formState);
	}, [formState, onConfigUpdate]);
	const { currentStepKey = 'authorization' } = formState.connection_status ?? { currentStepKey: 'authorization' };

	const setCurrentStepKey = useCallback((newStepKey) => {
		setFormState((prevFormState) => ({
			...prevFormState,
			connection_status: {
				...(prevFormState.connection_status ?? {}),
				currentStepKey: newStepKey,
			}
		}));
	}, [setFormState]);
	const prevStepHandler = useCallback(() => {
		let prevKey = getPrevKey(stepKeys, currentStepKey, false);
		if ('sync' === currentStepKey && 'success' === formState.connection_status?.status) {
			setFormState((prevFormState) => ({
				...prevFormState,
				connection_status: {
					...prevFormState.connection_status,
					status: 'idle',
				}
			}));
		} else {
			setCurrentStepKey(prevKey);
		}
	}, [currentStepKey, setCurrentStepKey, formState.connection_status?.status]);
	const nextStepHandler = useCallback(() => {
		setCurrentStepKey(getNextKey(stepKeys, currentStepKey, false));
	}, [currentStepKey, setCurrentStepKey, ]);

	const steps = [
		{
			stepKey: 'authorization',
			title: __('Airtable token', 'orders-sync-to-airtable-for-woocommerce'),
			children: <AccessTokenForm
				setFormState={ setFormState }
				onComplete={ currentStepKey === 'authorization' ? nextStepHandler : null }
				initialAccessToken={ formState.accessToken }
			/>,
		},
		{
			stepKey: 'template',
			title: __('Choose a template', 'orders-sync-to-airtable-for-woocommerce'),
			children: <ChooseATemplate
				template={ formState.template }
				setFormState={ setFormState }
				onComplete={ nextStepHandler }
			/>,
		},
		{
			stepKey: 'destination',
			title: __('Select the table', 'orders-sync-to-airtable-for-woocommerce'),
			children: <Destination
				template={ formState.template }
				templateProps={ templateProps }
				formState={ formState }
				setFormState={ setFormState }
				onComplete={ nextStepHandler }
			/>,
		},
		{
			stepKey: 'map_columns',
			title: formState.template === 'air_woo_sync_template' ? __('Verify data', 'orders-sync-to-airtable-for-woocommerce') : __('Map columns to each other', 'orders-sync-to-airtable-for-woocommerce'),
			children: formState.template && formState.baseId ? <MapColumns
				defaultMappingOptions={ defaultMappingOptions }
				isOptionAvailable={ isOptionAvailable }
				formState={ formState }
				setFormState={ setFormState }
				onComplete={ nextStepHandler }
			/> : null
		},
		{
			stepKey: 'sync',
			title: __('Sync your orders', 'orders-sync-to-airtable-for-woocommerce'),
			children: formState.mapping ? <Sync
				formState={ formState }
				setFormState={ setFormState }
				scheduledSyncTypes={ formOptions.scheduledSyncTypes }
			/> : null
		}
	];

	if (showWelcome) {
		return <Welcome onComplete={ () => {
			setShowWelcome(false)
		} } />;
	}

	return <>
		<Heading level={1}>{
			currentStepKey !== '' ?
				__('Set up the sync to Airtable', 'orders-sync-to-airtable-for-woocommerce')
				: null
		}</Heading>

		{
			<ButtonIcon
				buttonType="link"
				icon="arrow-left"
				iconPos="before"
				onClick={
					currentStepKey === 'authorization' ?
						() => {  setShowWelcome(true)  }
						: prevStepHandler
				}
			>{ __('Back', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
		}
		<Spacer size={10} />
		<StepsIndex
			steps={ steps }
			currentStepKey={ currentStepKey }
			stepsDone={ 'success' === formState.connection_status?.status ? stepKeys : stepKeys.slice(0, stepKeys.indexOf(currentStepKey)) }
		/>
		<Spacer size={64} />
		<Panel>
			<Steps steps={ steps } currentStepKey={ currentStepKey }></Steps>
		</Panel>
		<Spacer size={32} />
		<ConnectionStatus formState={ formState } currentStepKey={ currentStepKey } />
		{ /* add space below for dropdown select to have some free space */ }
		<div style={{ height: '200px' }} />
	</>
}
