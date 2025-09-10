import TranslationsContext from './TranslationsContext';
import DOMPurify from "dompurify";
import {useContext} from "react";


const replacePlaceholders = (string, placeholders) => {
	return Object.keys(placeholders).reduce(function (carry, placeholder) {
		return carry.replace(placeholder, placeholders[placeholder]);
	}, string)
}

const Translate = ({ string, placeholders = {} }) => {
	const i18n = useContext(TranslationsContext);
	return replacePlaceholders(string(i18n), placeholders);
}


export const TranslateHTML = ({ string, allowedHTMLTags = [], allowedHTMLAttrs = [], placeholders = null }) => {
	const i18n = useContext(TranslationsContext);
	let translatedString = string(i18n);
	if (placeholders) {
		translatedString = replacePlaceholders(translatedString, placeholders);
	}
	return <span dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(translatedString, { ALLOWED_TAGS: allowedHTMLTags, ALLOWED_ATTR: allowedHTMLAttrs }) }} />;

}

export default Translate;
