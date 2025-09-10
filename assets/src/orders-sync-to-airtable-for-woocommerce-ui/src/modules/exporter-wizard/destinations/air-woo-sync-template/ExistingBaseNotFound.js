import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import {Fragment, useContext} from "react";
import TranslationsContext from "../../../../utils/TranslationsContext";
import ButtonLinkIcon from "air-wp-sync-ui/library/components/content/button-link-icon";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import Button from "air-wp-sync-ui/library/components/form/button";

export default function ExistingBaseNotFound({  startBaseChecker, setShowDisconnectBaseConfirm, errors }) {
	const { __ } = useContext(TranslationsContext);
	return <>
			<Heading level={2}>{ __('Hello!', 'orders-sync-to-airtable-for-woocommerce') }</Heading><Spacer size={24} />


			{
				errors.length > 0 ?
					<>
						{
							errors.map((error, index) => {
								return <Fragment key={ index }><PrebuiltCallout type="error">{ error }</PrebuiltCallout><Spacer size={10} /></Fragment>;
							})
						}
						<Spacer size={24} />
						<Paragraph>{ __('Please disconnect it and create a new one.', 'orders-sync-to-airtable-for-woocommerce') }</Paragraph>
						<Spacer size={24} />
						<Button
							disabled={ false }
							buttonType="secondary"
							onClick={ () => {
								setShowDisconnectBaseConfirm(true)
							} }
						>{ __('Disconnect the base', 'orders-sync-to-airtable-for-woocommerce') }</Button>
					</>
					: <>
						<PrebuiltCallout type="error">{ __('Ooops, it seems we can no longer find your base...', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout>
						<Spacer size={24} />
						<Paragraph>{ __('Please check the permissions of your token and its validity.', 'orders-sync-to-airtable-for-woocommerce') }</Paragraph>
						<Spacer size={24} />
						<ButtonLinkIcon
							onClick={ startBaseChecker }
							icon={ 'open-external' }
							href="https://airtable.com/"
							target="_blank"
							rel="noreferrer"
						>{ __('Check the base', 'orders-sync-to-airtable-for-woocommerce') }</ButtonLinkIcon>
					</>
			}

	</>
}
