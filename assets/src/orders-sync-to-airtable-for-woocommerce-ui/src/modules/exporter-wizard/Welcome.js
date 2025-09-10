import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import {TranslateHTML} from "../../utils/Translate";
import Button from "air-wp-sync-ui/library/components/form/button";
import Columns from "air-wp-sync-ui/library/components/layout/columns";
import WooToAirtableImage from "../../resources/woo-to-airtable-image.webp";
import {useContext} from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import StaticFileContext from "../../utils/StaticFileContext";
import Panel from "air-wp-sync-ui/library/components/layout/panel";
export default function Welcome({ onComplete }) {
	const staticFileUriTransformer = useContext(StaticFileContext);
	const { __ } = useContext(TranslationsContext);
	return <Panel>
		<Spacer size={64} />
		<Columns style={{ alignItems: 'flex-start' }} columns={[
			{
				children: <>
					<Spacer size={16} />
					<Heading level="2">{ __('Welcome to Orders Sync to Airtable for WooCommerce', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
					<Spacer size={24} />
					<Paragraph weight="bold" fontSize="xl" style={{ lineHeight: 1.54 }}>
						<TranslateHTML
							string={({ __ }) => ( __('Easily sync your <strong>WooCommerce</strong> orders with <strong>Airtable</strong>.', 'orders-sync-to-airtable-for-woocommerce') )}
							allowedHTMLTags={['strong', 'br']}
						/>
					</Paragraph>
					<Spacer size={24} />
					<Button onClick={ onComplete }>{ __('Get started!', 'orders-sync-to-airtable-for-woocommerce') }</Button>
				</>,
				size: 8
			},
			{
				children: <img src={ staticFileUriTransformer(WooToAirtableImage) } alt={ __('WooCommerce to Airtable', 'orders-sync-to-airtable-for-woocommerce') } style={{ width: '100%', height: 'auto', maxWidth: '332px' }} width="512" height="512"/>,
				size: 4
			}
		]}/>

	</Panel>;
}
