import DetailedChoices from "air-wp-sync-ui/library/components/form/detailed-choices";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import {useContext} from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import {TranslateHTML} from "../../utils/Translate";
export default function ChooseATemplate({ setFormState, onComplete, template }) {
	const { __ } = useContext(TranslationsContext);
	return <>
		<Heading level={2}>{ __('Pick a template', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
		<Spacer size={32} />
		<DetailedChoices
			legend={ __('Pick a template', 'orders-sync-to-airtable-for-woocommerce') }
			selected={ template }
			choices={ [
				{
					label: __('WP connect WooCommerce Orders template', 'orders-sync-to-airtable-for-woocommerce'),
					description: <>
						<Paragraph>
							<TranslateHTML
								string={({ __ }) => (
									__( 'Use our <strong>ready-made template</strong> that works with <strong><a href="https://wpconnect.co/woocommerce-airtable-integration/" target="_blank">Air WP Sync for WooCommerce</a></strong>.', 'orders-sync-to-airtable-for-woocommerce' )
								)}
								allowedHTMLTags={[ 'a', 'strong' ]}
								allowedHTMLAttrs={[ 'href', 'target' ]}
							/>
						</Paragraph>
						<Paragraph>
							<TranslateHTML
								string={({ __ }) => ( __('It saves time by <strong>auto-linking fields</strong> — no need to make a table yourself.', 'orders-sync-to-airtable-for-woocommerce'))}
								allowedHTMLTags={[ 'strong' ]}
							/>
						</Paragraph>
					</>,
					value: 'air_woo_sync_template',
				},
				{
					label: __('Start from scratch', 'orders-sync-to-airtable-for-woocommerce'),
					description: <>
						<Paragraph>
							<TranslateHTML
								string={({ __ }) => ( __('Create your <strong>own table</strong> and match fields <strong>manually</strong>.', 'orders-sync-to-airtable-for-woocommerce')) }
								allowedHTMLTags={[ 'strong' ]}
							/>
						</Paragraph>
					</>,
					value: 'from_scratch',
				}
			]}
			onChange={(newValue) => {
				setFormState((prevFormState) => {
					return {
						...prevFormState,
						template: newValue
					};
				})
			}}
		/>
		<Spacer size={32} />
		<ButtonIcon icon="arrow-right" type="button" onClick={ onComplete }>{ __('Next step', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
	</>
}
