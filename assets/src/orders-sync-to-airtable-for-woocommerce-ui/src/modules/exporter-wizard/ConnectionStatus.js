import Heading from "air-wp-sync-ui/library/components/content/heading";
import { useContext } from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import BaseIcon from "air-wp-sync-ui/library/components/graphics/icons/Base.tsx";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import ButtonGroup from "air-wp-sync-ui/library/components/form/button-group";
import ButtonLinkIcon from "air-wp-sync-ui/library/components/content/button-link-icon";
import Panel from "air-wp-sync-ui/library/components/layout/panel";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import { TranslateHTML } from "../../utils/Translate";

export default function ConnectionStatus({ formState, currentStepKey }) {
	const { __ } = useContext(TranslationsContext);
	const baseInfo = formState.connection_status?.baseInfo;

	if ('sync' !== currentStepKey || formState.connection_status.status === 'loading') {
		return null;
	}
	let statusLabel;
	switch (formState.connection_status.status) {
		case 'success':
			statusLabel = __('Successful', 'orders-sync-to-airtable-for-woocommerce');
			break;
		case 'error':
			statusLabel = __('Error', 'orders-sync-to-airtable-for-woocommerce');
			break;
		default:
			statusLabel = __('Not synced yet', 'orders-sync-to-airtable-for-woocommerce')
	}
	if ('idle' === formState.connection_status.status && formState.connection_status.synced) {
		statusLabel = __('Successful', 'orders-sync-to-airtable-for-woocommerce');
	}
	return <Panel>
		<Heading level={3}>{__('Your synchronized Airtable base:', 'orders-sync-to-airtable-for-woocommerce')}</Heading>
		{
			baseInfo ?
				<div style={{
					display: 'flex',
					gap: '8px',
					alignItems: 'center',
				}}>
					<BaseIcon/>
					<Paragraph fontSize="xl" weight="bold" style={{
						marginBottom: 0,
						marginRight: '8px',
					}}><strong>{baseInfo.baseName}</strong></Paragraph>
					<ButtonGroup gap={16}>
						<ButtonLinkIcon
							buttonType="link"
							icon="open-external"
							href={baseInfo.baseURL}
							target="_blank"
							rel="noreferrer"
							style={{
								color: 'var(--airwpsync--color--primary-300)'
							}}
						>{__('View on airtable', 'orders-sync-to-airtable-for-woocommerce')}</ButtonLinkIcon>
					</ButtonGroup>
				</div>
				: null
		}
		<Spacer size={32} />
		<Heading level={3}>{__('Connection status:', 'orders-sync-to-airtable-for-woocommerce')}</Heading>
		{
			<>
				<Spacer size={16} />
				<Paragraph>
					<TranslateHTML
						// translators: %s connection status ("Successful", "Error", "Not synced yet")
						string={ ({ __ }) => __('<strong>Last Sync Status: </strong>%s', 'orders-sync-to-airtable-for-woocommerce') }
						placeholders={ {'%s': statusLabel } }
						allowedHTMLTags={['strong']}
					/>
				</Paragraph>
				{
					formState.connection_status.last_updated_formatted ?
						<Paragraph>
							<TranslateHTML
								// translators: %s last connection updated date
								string={ ({ __ }) => __('<strong>Date: </strong>%s', 'orders-sync-to-airtable-for-woocommerce') }
								placeholders={ {'%s': formState.connection_status.last_updated_formatted } }
								allowedHTMLTags={['strong']}
							/>
						</Paragraph>
						: null
				}
			</>
		}
		{
			formState.connection_status?.logs_url ?
				<>
					<Spacer size={16} />
					<ButtonLinkIcon
						buttonType="secondary"
						icon="download"
						iconPos="before"
						underlined={ false }
						href={ formState.connection_status.logs_url }
						download
					>{ __('Download logs', 'orders-sync-to-airtable-for-woocommerce') }</ButtonLinkIcon>
				</>
				: null
		}
	</Panel>

}
