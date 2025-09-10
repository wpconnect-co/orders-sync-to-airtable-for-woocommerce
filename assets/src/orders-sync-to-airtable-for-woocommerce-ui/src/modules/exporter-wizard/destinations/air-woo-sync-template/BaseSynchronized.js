import Heading from "air-wp-sync-ui/library/components/content/heading";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import {useContext} from "react";
import TranslationsContext from "../../../../utils/TranslationsContext";
import BaseIcon from "air-wp-sync-ui/library/components/graphics/icons/Base.tsx";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import ButtonGroup from "air-wp-sync-ui/library/components/form/button-group";
import ButtonLinkIcon from "air-wp-sync-ui/library/components/content/button-link-icon";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import {TranslateHTML} from "../../../../utils/Translate";

export default function BaseSynchronized({ onComplete, successMessage, latestTemplateVersion, baseInfo, disconnectBaseResult, setShowDisconnectBaseConfirm, templateURL }) {
	const { __ } = useContext(TranslationsContext);
	return <>

		<Heading level={2}>{__('Your synchronized Airtable base', 'orders-sync-to-airtable-for-woocommerce')}</Heading><Spacer size={24}/>

		{ disconnectBaseResult }

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
				<ButtonIcon
					buttonType="link"
					icon="cross"
					iconPos="before"
					fontSize="xs"
					onClick={() => {
						setShowDisconnectBaseConfirm(true)
					}}
				>{__('Disconnect the base', 'orders-sync-to-airtable-for-woocommerce')}</ButtonIcon>
			</ButtonGroup>
		</div>
		{
			// Display success message if we just checked the base.
			successMessage ?
				<><Spacer size={24}/><PrebuiltCallout type="success"><TranslateHTML
					string={({__}) => successMessage}
					allowedHTMLTags={['strong']}
				/></PrebuiltCallout></>
				: null
		}
		<Spacer size={24}/>
		<PrebuiltCallout type="info">
			<TranslateHTML
				string={({__}) => __('<strong>Please do not modify the template we provided, </strong>this ensures a smooth setup and proper synchronization.', 'orders-sync-to-airtable-for-woocommerce')}
				allowedHTMLTags={['strong']}
			/>
		</PrebuiltCallout>
		{
			latestTemplateVersion !== baseInfo.templateVersion ?
				<>
					<Spacer size={24}/>
					<PrebuiltCallout type="info" iconAlignment="center">
						<ButtonLinkIcon
							icon={'open-external'}
							buttonType="link"
							href={templateURL}
							target="_blank"
							rel="noreferrer"
							style={{ marginRight: '32px' }}
						>{
							// Translators: %s latest template version.
							__('A new version of the template is available (%s)', 'orders-sync-to-airtable-for-woocommerce').replace('%s', latestTemplateVersion)
						}</ButtonLinkIcon>

					</PrebuiltCallout>
				</>
				: null
		}

		<Spacer size={32}/>
		<ButtonIcon icon="arrow-right" type="button" onClick={ onComplete }>{ __('Next step', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>


	</>
}
