import Heading from "air-wp-sync-ui/library/components/content/heading";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import {useContext} from "react";
import TranslationsContext from "../../../../utils/TranslationsContext";
import ButtonLinkIcon from "air-wp-sync-ui/library/components/content/button-link-icon";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import {TranslateHTML} from "../../../../utils/Translate";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";

export default function BaseIdle({ startBaseChecker, templateURL }) {
	const { __ } = useContext(TranslationsContext);
	return <>
		<Heading level={2}>{ __('Hello!', 'orders-sync-to-airtable-for-woocommerce') }</Heading><Spacer size={24} />
			<PrebuiltCallout type="warning"><TranslateHTML string={({ __ }) => __('Please <strong>DO NOT MODIFY the template</strong> we provide at this time. This will ensure you can take advantage of the auto-mapping.', 'orders-sync-to-airtable-for-woocommerce')} allowedHTMLTags={['strong']} /></PrebuiltCallout><Spacer size={24} />
			<Paragraph color="primary-300">{ __('To set up the sync, follow these 4 steps:', 'orders-sync-to-airtable-for-woocommerce') }</Paragraph>
			<ol style={{ color: 'var(--airwpsync--color--primary-300)', fontSize: 'var(--airwpsync-t-font-size--base)' }}>
				<li><TranslateHTML
					string={({ __ }) => (
						__('Click on “<strong>Copy the Airtable template</strong>” button below.', 'orders-sync-to-airtable-for-woocommerce')
					)}
					allowedHTMLTags={['strong']}
				/></li>
				<li><TranslateHTML
					string={({ __ }) => (
						__('Click on the “<strong>Copy base</strong>” button on Airtable.', 'orders-sync-to-airtable-for-woocommerce')
					)}
					allowedHTMLTags={['strong']}
				/></li>
				<li><TranslateHTML
					string={({ __ }) => (__('Choose the <strong>workspace</strong> linked to your token.', 'orders-sync-to-airtable-for-woocommerce') ) }
					allowedHTMLTags={['strong']}
				/></li>
				<li>{ __('Come back to this screen.', 'orders-sync-to-airtable-for-woocommerce') }</li>
			</ol>
			<Spacer size={ 24 } />
			<ButtonLinkIcon onClick={startBaseChecker} icon={ 'open-external' } href={ templateURL } target="_blank" rel="noreferrer"><TranslateHTML string={({__}) => __('Copy the <strong>Airtable template</strong>', 'orders-sync-to-airtable-for-woocommerce') } allowedHTMLTags={['strong']}/></ButtonLinkIcon>

	</>
}
