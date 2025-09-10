import {useCallback, useContext, useEffect, useState} from "react";
import TranslationsContext from "../../../../utils/TranslationsContext";
import FetcherContext from "../../../../utils/FetcherContext";
import {fetchForm, FORM_STATE} from "../../../../utils/fetcher";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import BaseChecking from "./BaseChecking";
import BaseSynchronized from "./BaseSynchronized";
import ExistingBaseNotFound from "./ExistingBaseNotFound";
import BaseError from "./BaseError";
import BaseIdle from "./BaseIdle";
import PopUp from "air-wp-sync-ui/library/components/layout/pop-up";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import ButtonGroup from "air-wp-sync-ui/library/components/form/button-group";
import Button from "air-wp-sync-ui/library/components/form/button";

const CHECK_TEMPLATE_MAX_RETRIES = 10;

export default function AirWooSyncTemplate({ accessToken, setFormState, templateURL, onComplete, latestTemplateVersion, baseInfo, setBaseInfo }) {
	const { __ } = useContext(TranslationsContext);
	const fetchFn = useContext(FetcherContext);
	const [ isCheckingBase, setIsCheckingBase ] = useState(false);
	const [ checkBaseFormStatus, setCheckBaseFormStatus ] = useState(FORM_STATE);
	const [ disconnectBaseFormStatus, setDisconnectBaseFormStatus ] = useState(FORM_STATE);
	const [ showDisconnectBaseConfirm, setShowDisconnectBaseConfirm ] = useState(false);
	const [ retries, setRetries ] = useState(CHECK_TEMPLATE_MAX_RETRIES);
	const resetCheckAndRetries = useCallback(() => {
		setIsCheckingBase(false);
		setRetries(CHECK_TEMPLATE_MAX_RETRIES);
	}, []);
	const startBaseChecker = useCallback(() => {
		const formData = new FormData();
		formData.set('accessToken', accessToken);
		setIsCheckingBase(true);
		setRetries(retries - 1);
		setDisconnectBaseFormStatus(FORM_STATE);
		fetchForm(fetchFn, 'check-base-template', setCheckBaseFormStatus, formData);

	}, [retries, fetchFn, setDisconnectBaseFormStatus, accessToken]);

	const disconnectBase = useCallback(() => {
		const formData = new FormData();
		setCheckBaseFormStatus(FORM_STATE);
		fetchForm(fetchFn, 'disconnect-base', setDisconnectBaseFormStatus, formData);

	}, [fetchFn, setCheckBaseFormStatus]);

	useEffect(() => {
		let t;
		if (checkBaseFormStatus.success !== null && checkBaseFormStatus.error && checkBaseFormStatus.code !== 404) {
			resetCheckAndRetries();
			setCheckBaseFormStatus({
				...checkBaseFormStatus,
				success: null,
			});
		} else if (checkBaseFormStatus.success !== null && !checkBaseFormStatus.isLoading) {
			if (checkBaseFormStatus.success === false) {
				if (retries > 0) {
					t = setTimeout(() => {
						startBaseChecker();
					}, 5000);
				} else {
					resetCheckAndRetries();
					setCheckBaseFormStatus({
						...checkBaseFormStatus,
						success: null,
					});
				}
			} else {
				const { baseName, baseURL, templateVersion, baseId, tableId, mappingInit, mappingAuto } = checkBaseFormStatus.data;
				setBaseInfo({
					baseId,
					baseName,
					baseURL,
					templateVersion,
					status: 'success'
				})
				setFormState((prevState) => {
					const mapping = Object.keys(mappingAuto).map((wordpress)=> {
						return {
							wordpress,
							airtable: mappingAuto[wordpress],
						}
					});
					return {
						...prevState,
						baseId,
						tableId,
						mappingInit,
						mappingAuto,
						mapping,
					};
				});
				resetCheckAndRetries();
			}
		}
		return () => {
			clearTimeout(t);
		}
	}, [checkBaseFormStatus, setFormState, retries, startBaseChecker, resetCheckAndRetries, setBaseInfo]);

	useEffect(() => {
		if (true === disconnectBaseFormStatus.success) {
			setBaseInfo({
				status: 'idle'
			});
			setFormState((prevFormState) => {
				return  {
					...prevFormState,
					baseId: null,
					tableId: null,
					mappingAuto: {},
					mappingInit: { "orders" : []},
					mapping: [],
				};
			})
		}
	}, [disconnectBaseFormStatus, setBaseInfo]);


	let state = 'idle';
	if (isCheckingBase) {
		state = 'checking-base';
	} else if ( checkBaseFormStatus.success === true || baseInfo.baseName ) {
		state = 'success';
	} else if ('base-not-found' === baseInfo.status) {
		state = 'base-not-found';
	} else if ( checkBaseFormStatus.error ) {
		state = 'error';
	}

	const errors = checkBaseFormStatus.error ? (typeof checkBaseFormStatus.error === 'string' ? [checkBaseFormStatus.error] : checkBaseFormStatus.error) : [];

	let disconnectBaseResult;
	switch (disconnectBaseFormStatus.success) {
		case true:
			disconnectBaseResult = <><PrebuiltCallout type="success">{ __('Base disconnected', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout><Spacer size={24} /></>;
			break;
		case false:
			disconnectBaseResult = <><PrebuiltCallout type="error">{ disconnectBaseFormStatus.error }</PrebuiltCallout><Spacer size={24} /></>;
			break;
		default:
			disconnectBaseResult = null;
	}

	let panel;
	switch (state) {
		case 'checking-base':
			panel = <BaseChecking retries={ retries } setRetries={ setRetries } />;
			break;

		case 'success':
			panel = <BaseSynchronized
				latestTemplateVersion={ latestTemplateVersion }
				baseInfo={ baseInfo }
				successMessage={ checkBaseFormStatus.success === true ? checkBaseFormStatus.data.message : false }
				disconnectBaseResult={ disconnectBaseResult }
				templateURL={ templateURL }
				setShowDisconnectBaseConfirm={ setShowDisconnectBaseConfirm }
				onComplete={ onComplete }
			/>;
			break;

		case 'base-not-found':
			panel = <ExistingBaseNotFound
				startBaseChecker={ startBaseChecker }
				setShowDisconnectBaseConfirm={ setShowDisconnectBaseConfirm }
				errors={ errors }
			/>;
			break;

		case 'error':
			panel = <BaseError
				startBaseChecker={ startBaseChecker }
				actionsDisabled={ disconnectBaseFormStatus.isLoading }
				errors={ errors }
				baseInfo={ baseInfo }
				baseNotFound={ 'base-not-found' === baseInfo.status }
			/>
			break;

		default:
			panel = <BaseIdle
				startBaseChecker={ startBaseChecker }
				templateURL={ templateURL }
			/>;
	}

	return <>
		{ panel }
		<PopUp isOpen={ showDisconnectBaseConfirm } setIsOpen={ setShowDisconnectBaseConfirm }>
			<Heading level={ 2 } semanticLevel={ 3 }>{ __('Disconnect the Airtable base', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
			<Spacer size={ 24 } />
			<PrebuiltCallout type="warning">{ __('Are you sure you want to proceed?', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout>
			<Spacer size={ 24 } />
			<ButtonGroup>
				<Button
					onClick={ () => {
						setShowDisconnectBaseConfirm(false);
						disconnectBase();
					} }
				>{ __('Yes, I want to disconnect my base', 'orders-sync-to-airtable-for-woocommerce') }</Button>
				<Button
					buttonType="secondary"
					onClick={ () => {
						setShowDisconnectBaseConfirm(false);
					} }
				>{ __('No, keep it', 'orders-sync-to-airtable-for-woocommerce') }</Button>
			</ButtonGroup>
		</PopUp>
	</>;
}
