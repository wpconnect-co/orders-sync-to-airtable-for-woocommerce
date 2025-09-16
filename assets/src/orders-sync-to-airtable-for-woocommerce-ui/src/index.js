import React from 'react';
import ReactDOM from 'react-dom/client';
import demo from './demo';
import fetcherFactory from "./utils/fetcher";
import TranslationsContext from "./utils/TranslationsContext";
import FetcherContext from "./utils/FetcherContext";
import ExporterWizard from "./modules/exporter-wizard/ExporterWizard";
import StaticFileContext, {staticFileUriTransformerFactory} from "./utils/StaticFileContext"

function renderModule({ id = 'orders-sync-to-airtable-for-woocommerce-exporter-wizard', module, fetchFn, i18n, buildPathUri, ...props }) {
    const root = ReactDOM.createRoot(document.getElementById(id));

	let moduleInstance = null;
	switch (module) {
		case 'exporter-wizard':
			moduleInstance = <ExporterWizard {...props} />;
			break;

		default:
			moduleInstance = 'Unknown module ' + module;
	}
    root.render(
        <React.StrictMode>
			<StaticFileContext.Provider value={ staticFileUriTransformerFactory(buildPathUri) }>
				<TranslationsContext.Provider value={ i18n }>
					<FetcherContext.Provider value={ fetchFn }>

						{ moduleInstance }

					</FetcherContext.Provider>
				</TranslationsContext.Provider>
			</StaticFileContext.Provider>
        </React.StrictMode>
    );
}

if (process.env.NODE_ENV === 'development') {
    demo(renderModule);
}

window.syncWoocommerceOrdersToAirtableRenderUIModule = renderModule;
window.syncWoocommerceOrdersToAirtableFetcherFactory = fetcherFactory;
