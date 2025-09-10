
export default function fetcherFactory(config) {
	return async function (key, formData) {
	    if (!config[key]) {
			throw new Error('No fetch config for this key');
		}
		const options = {
			...(config[key].options ?? { method: 'post' }),
			body: formData
		}
		const response = await fetch(config[key].url, options);

		return { ...(await response.json()), code: response.status };
	};
}

export const FORM_STATE = { isLoading: false, success: null, error: null, code: 0 };

export async function fetchForm(fetchFn, configKey, setFormStatus, formData) {
	setFormStatus({ ...FORM_STATE, isLoading: true });
	const response = await fetchFn(configKey, formData);
	if (response.success) {
		setFormStatus({ isLoading: false, error: null, success: true, data: response.data, code: response.code });
	} else {
		console.error('fetchForm', response)
		setFormStatus({ isLoading: false, error: response.data, success: false, data: null, code: response.code });
	}
	return response;
}
