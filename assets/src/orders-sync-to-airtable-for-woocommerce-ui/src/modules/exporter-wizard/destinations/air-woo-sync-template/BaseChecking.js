import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import Button from "air-wp-sync-ui/library/components/form/button";
import {useContext} from "react";
import TranslationsContext from "../../../../utils/TranslationsContext";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import {TranslateHTML} from "../../../../utils/Translate";

export default function BaseChecking({ retries, setRetries }) {
	const { __ } = useContext(TranslationsContext);
	return <>

		<Heading level={2}>{ __('Copy the Airtable template', 'orders-sync-to-airtable-for-woocommerce') }</Heading><Spacer size={24} />
		<PrebuiltCallout type="warning"><TranslateHTML string={({ __ }) => __('Please <strong>DO NOT MODIFY the template</strong> we provide at this time. This will ensure you can take advantage of the auto-mapping.', 'orders-sync-to-airtable-for-woocommerce')} allowedHTMLTags={['strong']} /></PrebuiltCallout><Spacer size={24} />
		<PrebuiltCallout type="loading"><TranslateHTML string={({ __ }) => __('Checking if the <strong>template</strong> was added to your <strong>Airtable</strong>…', 'orders-sync-to-airtable-for-woocommerce') } allowedHTMLTags={['strong']} /></PrebuiltCallout>
		<Spacer size={32} />
		<Button
			buttonType="link"
			type="button"
			disabled={ retries <= 1 }
			onClick={ () => {
				// Check only once more, then display the result
				setRetries(1);
			} }
		>{ __('I checked, it’s created!', 'orders-sync-to-airtable-for-woocommerce') }</Button>
	</>
}
