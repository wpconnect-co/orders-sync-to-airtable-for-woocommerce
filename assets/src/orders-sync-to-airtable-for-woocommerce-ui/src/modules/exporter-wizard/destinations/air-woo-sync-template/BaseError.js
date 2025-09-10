import ButtonLinkIcon from "air-wp-sync-ui/library/components/content/button-link-icon";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import {Fragment, useContext} from "react";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import {TranslateHTML} from "../../../../utils/Translate";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import TranslationsContext from "../../../../utils/TranslationsContext";
import ButtonGroup from "air-wp-sync-ui/library/components/form/button-group";

export default function BaseError({ actionsDisabled, startBaseChecker, errors, baseNotFound }) {
	const { __ } = useContext(TranslationsContext);

	return <>

		<Heading level={2}>{ __('Hello!', 'orders-sync-to-airtable-for-woocommerce') }</Heading><Spacer size={24} />
		<PrebuiltCallout type="warning"><TranslateHTML string={({ __ }) => __('Please <strong>DO NOT MODIFY the template</strong> we provide at this time. This will ensure you can take advantage of the auto-mapping.', 'orders-sync-to-airtable-for-woocommerce')} allowedHTMLTags={['strong']} /></PrebuiltCallout><Spacer size={24} />
		{
			errors.map((error, index) => {
				return <Fragment key={ index }><PrebuiltCallout type="error">{ error }</PrebuiltCallout><Spacer size={10} /></Fragment>;
			})
		}
		{
			!baseNotFound ?
				<PrebuiltCallout type="error">
					<TranslateHTML
						string={({ __ }) => __('Please <strong>reset our original template</strong> before to restart the verification.', 'orders-sync-to-airtable-for-woocommerce')}
						allowedHTMLTags={['strong']}
					/>
				</PrebuiltCallout>
				: null
		}
		<Spacer size={32} />

		<ButtonGroup>
			<ButtonLinkIcon
				disabled={ actionsDisabled }
				onClick={startBaseChecker}
				icon={ 'open-external' }
				href="https://airtable.com/"
				target="_blank"
				rel="noreferrer"
			>{ __('Check the base', 'orders-sync-to-airtable-for-woocommerce') }</ButtonLinkIcon>
		</ButtonGroup>

	</>
}
