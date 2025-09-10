import AirWooSyncTemplate from "./destinations/air-woo-sync-template/AirWooSyncTemplate";
import FromScratch from "./destinations/from-scratch/FromScratch";
import {useCallback} from "react";

export default function Destination({ templateProps = {}, formState, setFormState, onComplete, template }) {
	const baseInfo = formState.connection_status?.baseInfo ?? {};
	const setBaseInfo = useCallback((newBaseInfo) => {
		setFormState((prevFormState) => {
			return {
				...prevFormState,
				connection_status: {
					...(prevFormState.connection_status ?? {}),
					baseInfo: newBaseInfo
				}
			}
		})
	}, [setFormState]);

	switch (template) {
		case 'air_woo_sync_template':
			return formState.accessToken ? <AirWooSyncTemplate { ...{ ...(templateProps['air_woo_sync_template'] ?? {}), onComplete, setFormState, accessToken: formState.accessToken, baseInfo, setBaseInfo } } /> : null;
		case 'from_scratch':
			const { accessToken, baseId, tableId } = formState;
			return <FromScratch {  ...{  ...(templateProps['from_scratch'] ?? {}), accessToken, baseId, tableId, onComplete, setFormState, baseInfo, setBaseInfo } } />;
		default:
			console.error('Unknown template', template);
			return null;
	}
}
