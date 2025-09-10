import Columns from "air-wp-sync-ui/library/components/layout/columns";
import PasswordLikeInput from "air-wp-sync-ui/library/components/form/password-like-input";
import {useContext} from "react";
import TranslationsContext from "../../utils/TranslationsContext";
import FormRow from "air-wp-sync-ui/library/components/form/form-row";
import HelpLink from "air-wp-sync-ui/library/components/content/help-link";
import ButtonIcon from "air-wp-sync-ui/library/components/form/button-icon";
import Spacer from "air-wp-sync-ui/library/components/layout/spacer";
import {PrebuiltCallout} from "air-wp-sync-ui/library/components/content/callout";
import Heading from "air-wp-sync-ui/library/components/content/heading";
import Paragraph from "air-wp-sync-ui/library/components/content/paragraph";
import {useAccessTokenForm} from "../../models/access-token";

export default function AccessTokenForm({ setFormState, onComplete, initialAccessToken = '' }) {
	const { __ } = useContext(TranslationsContext);
	const { submit, updateAccessToken, accessToken, accessTokenStatus, accessTokenKeyStatus, formStatus } = useAccessTokenForm({
		initialAccessToken,
		onComplete: function ({ accessToken }) {
			setFormState((prevState) => {
				return {
					...prevState,
					accessToken,
				};
			});
			onComplete();
		}
	})


	return <>
		<Columns columns={[
			{
				size: 7,
				children: <>
					<form onSubmit={submit}>
						<Heading level={2}>{ __('Step 1: Connect to Airtable', 'orders-sync-to-airtable-for-woocommerce') }</Heading>
						<Spacer size={32} />
						<Paragraph>{ __('To get started, enter your Airtable access token below.', 'orders-sync-to-airtable-for-woocommerce') }</Paragraph>
						<Spacer size={16} />
						<PrebuiltCallout type="warning" fontSize="sm">{ __('When creating your Airtable access token, make sure to select these permissions:\n' +
							'data.records:read, data.records:write, schema.bases:read, and schema.bases:write.\n' +
							'Also, choose the option: “All current and future bases in all current and future workspaces.', 'orders-sync-to-airtable-for-woocommerce') }</PrebuiltCallout>
						<Spacer size={16} />
						<FormRow>
							<PasswordLikeInput
								label={ __('Access token', 'orders-sync-to-airtable-for-woocommerce') }
								value={ accessToken }
								onChange={ (event) => {
									const newAccessToken = event.target.value;
									updateAccessToken(newAccessToken);
								} }
								placeholder={ __('e.g.: 2fdfd***dgtye', 'orders-sync-to-airtable-for-woocommerce') }
								readOnly={ formStatus.isLoading }
								status={ accessTokenStatus }
								showPassword={ !initialAccessToken || initialAccessToken !== accessToken }
							/>
							<HelpLink href="https://www.youtube.com/watch?v=z6rZU3iXzwI" text={ __('How to create an Airtable access token?', 'orders-sync-to-airtable-for-woocommerce') } />
							{
								formStatus.error ? <>
									<Spacer size={16} />
									<PrebuiltCallout type="error">{ formStatus.error }</PrebuiltCallout>
									<Spacer size={32} />
								</> : <Spacer size={64} />
							}
						</FormRow>
						{
							'valid' === accessTokenKeyStatus && 'valid' === accessTokenStatus ?
								<ButtonIcon
									type="button"
									icon="arrow-right"
									onClick={() => {
										setFormState((prevState) => {
											return {
												...prevState,
												accessToken,
											};
										})
										onComplete();
									}}
								>{ __('Next step', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
								: <ButtonIcon
									type="button"
									icon="verify"
									disabled={ 'valid' !== accessTokenKeyStatus || formStatus.isLoading }
									onClick={() => {
										submit()
									}}
								>{ __('Check', 'orders-sync-to-airtable-for-woocommerce') }</ButtonIcon>
						}

					</form>
				</>
			}
		]}/>
	</>
};
