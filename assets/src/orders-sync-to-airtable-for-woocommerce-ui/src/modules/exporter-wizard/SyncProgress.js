import Heading from "air-wp-sync-ui/library/components/content/heading";
import {useCallback, useContext, useEffect, useState} from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import ProgressBar from "air-wp-sync-ui/library/components/graphics/progress-bar";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import {TranslateHTML} from "../../utils/Translate";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import FetcherContext from "../../utils/FetcherContext";
import {fetchForm, FORM_STATE} from "../../utils/fetcher";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";

export default function SyncProgress({ formState, setFormState, isInitialLoading }) {
	const { __ } = useContext(TranslationsContext);
	const fetchFn = useContext(FetcherContext);
	const [ progressInfo, setProgressInfo ] = useState({});
	const [ checkProgressFormStatus, setCheckProgressFormStatus ] = useState(FORM_STATE);
	const [ cancelSyncFormStatus, setCancelSyncFormStatus ] = useState(FORM_STATE);
	const updateProgress = useCallback(() => {
		if (isInitialLoading) {
			return;
		}
		const formData = new FormData();
		try {
			fetchForm(fetchFn, 'check-sync-progress', setCheckProgressFormStatus, formData);
		} catch (e) {
			setCheckProgressFormStatus({
				isLoading: false,
				error: e.message,
				success: null,
				data: null
			});
		}
	}, [ fetchFn, setCheckProgressFormStatus, isInitialLoading ]);

	const cancelSync = useCallback(async () => {
		setFormState((prevFormState) => ({
			...prevFormState,
			connection_status: {
				...prevFormState.connection_status,
				...{
					status: 'idle',
				}
			}
		}));
		const formData = new FormData();
		try {
			await fetchForm(fetchFn, 'cancel-sync', setCancelSyncFormStatus, formData);
		} catch (e) {
			setCancelSyncFormStatus({
				isLoading: false,
				error: e.message,
				success: null,
				data: null
			});
		}
	}, [fetchFn, setFormState, setCancelSyncFormStatus]);

	useEffect(() => {
		let t;

		if (isInitialLoading) {
			return;
		}

		if (checkProgressFormStatus.success === true && !checkProgressFormStatus.isLoading) {
			const {remaining_minutes, ratio, time_spent, count_processed = -1, count_deleted = -1, as_other_pending_actions, logs_url, last_updated_formatted} = checkProgressFormStatus.data;
			setProgressInfo({
				remaining_minutes,
				time_spent,
				as_other_pending_actions,
				// Don't allow ratio to move back...
				ratio: progressInfo.ratio > ratio ? progressInfo.ratio : ratio
			})

			if (ratio === 1) {
				setFormState((prevFormState) => ({
					...prevFormState,
					connection_status: {
						...prevFormState.connection_status,
						...{
							status: 'success',
							synced: true,
							time_spent,
							count_processed,
							count_deleted,
							logs_url,
							last_updated_formatted,
						}
					}
				}));
			} else if (ratio < 1) {
				t = setTimeout(() => {
					updateProgress();
				}, 3000);
			}
		} else if (checkProgressFormStatus.success === false && !checkProgressFormStatus.isLoading) {
			setFormState((prevFormState) => ({
				...prevFormState,
				connection_status: {
					...prevFormState.connection_status,
					...{
						status: checkProgressFormStatus.error.status,
						errors: checkProgressFormStatus.error.errors,
						logs_url: checkProgressFormStatus.error.logs_url,
					}
				}
			}));
		} else if (checkProgressFormStatus.success === null && !checkProgressFormStatus.isLoading) {
			updateProgress();
		}
		return () => {
			clearTimeout(t);
		}
	}, [checkProgressFormStatus, updateProgress, setFormState, isInitialLoading, progressInfo.ratio]);

	return <>
		<Heading level={2}>{ __('Syncing your orders…', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		<Spacer size={32} />
		<ProgressBar
			ratio={ progressInfo.ratio ?? 0 }
		>{ (id) => <div id={ id } className="screen-reader-text">{ __('Export progress', 'orders-sync-to-airtable-for-woocommerce') }</div> }</ProgressBar>
		{
			progressInfo.remaining_minutes ?
				<>
					<Spacer size={32} />
					<Paragraph><TranslateHTML
						string={ ({ _n }) => (
							// translators: %d remaining minutes.
							_n(
								'This may take about <strong>%d minute</strong — please wait.',
								'This may take about <strong>%d minutes</strong> — please wait.',
								progressInfo.remaining_minutes,
								'orders-sync-to-airtable-for-woocommerce'
							)
						) }
						allowedHTMLTags={['strong']}
						placeholders={ { '%d': progressInfo.remaining_minutes } }
					/></Paragraph>
				</>
				: null
		}
		{
			progressInfo.as_other_pending_actions > 5 ?
				<>
					<Spacer size={32} />
					<PrebuiltCallout type="warning"><TranslateHTML
						string={({ __ }) => (
							// translators: %1$d count other action scheduler pending actions, %2$s URL to Action Scheduler screen.
							__( 'There are %1$d in the queue. This may slow things down. <a href="%2$s" target="_blank">See all queued tasks</a>.', 'orders-sync-to-airtable-for-woocommerce' )
						)}
						placeholders={{
							'%1$d': progressInfo.as_other_pending_actions,
							'%2$s': '/wp-admin/tools.php?page=action-scheduler&status=pending',
						}}
						allowedHTMLTags={[ 'a' ]}
						allowedHTMLAttrs={[ 'href', 'target' ]}
					/></PrebuiltCallout>
				</>
				: null
		}
		<Spacer size={32} />
		{ !isInitialLoading && progressInfo.remaining_minutes ? <ButtonIcon
			buttonType="link"
			icon="cross"
			iconPos="before"
			style={ {
			   color: 'var(--airwpsync--color--error)'
		   	}}
			disabled={ cancelSyncFormStatus.isLoading }
			onClick={ () => {
				cancelSync();
			}}
		>{ __('Cancel sync', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon> : null }
	</>
}
