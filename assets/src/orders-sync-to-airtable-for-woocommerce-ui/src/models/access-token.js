import {useContext, useEffect, useState} from "react";
import FetcherContext from "../utils/FetcherContext";
import {fetchForm, FORM_STATE} from "../utils/fetcher";

export function useAccessTokenForm({ initialAccessToken, onComplete }) {
	const fetchFn = useContext(FetcherContext);

	const [accessToken, setAccessToken] = useState(initialAccessToken);
	const [accessTokenStatus, setAccessTokenStatus] = useState('idle');
	const [accessTokenKeyStatus, setAccessTokenKeyStatus] = useState(initialAccessToken !== '' ? 'valid' : 'idle');
	const [formStatus, setFormStatus] = useState(FORM_STATE);

	const submit = async (event) => {
		if (event) {
			event.preventDefault();
		}
		const formData = new FormData();
		formData.append('accessToken', accessToken);
		await fetchForm(fetchFn, 'check-access-token', setFormStatus, formData);
	};
	useEffect(() => {
		let t;
		if (formStatus.success && 'valid' === accessTokenKeyStatus) {
			setAccessTokenStatus('valid');
			if (!onComplete) {
				setFormStatus(FORM_STATE);
				return;
			}
			t = setTimeout(() => {
				onComplete({
					accessToken
				});
				setFormStatus(FORM_STATE);
			}, 500);
		} else if (false === formStatus.success && 'valid' === accessTokenKeyStatus) {
			setAccessTokenStatus('invalid');
			setAccessTokenKeyStatus('invalid');
		} else if ('idle' === accessTokenKeyStatus) {
			setAccessTokenStatus('idle')
		}
		return () => {
			clearTimeout(t);
		}
	}, [formStatus.success, accessTokenKeyStatus, onComplete, accessToken]);

	const updateAccessToken = (newAccessToken) => {
		setAccessToken(newAccessToken);
		setAccessTokenStatus('idle')
		setAccessTokenKeyStatus('' !== newAccessToken ? 'valid' : 'idle');
		setFormStatus(FORM_STATE);
	}

	return { submit, updateAccessToken, accessToken, accessTokenStatus, accessTokenKeyStatus, formStatus }
}
