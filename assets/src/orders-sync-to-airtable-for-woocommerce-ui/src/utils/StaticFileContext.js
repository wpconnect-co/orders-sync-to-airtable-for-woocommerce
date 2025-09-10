import React from "react";

const StaticFileContext = React.createContext();
export default StaticFileContext;

export function staticFileUriTransformerFactory(buildPathUri) {
	return (staticAssetPath) => {
		return buildPathUri ? buildPathUri + staticAssetPath.replace('/static', '') : staticAssetPath;
	}
}
