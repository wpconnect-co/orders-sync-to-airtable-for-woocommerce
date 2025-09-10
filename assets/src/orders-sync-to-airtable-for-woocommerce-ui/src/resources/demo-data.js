export const airtableFields = [
	{
		"type": "singleLineText",
		"id": "fldx9NuchslbT2SgY",
		"name": "Order #"
	},
	{
		"type": "singleLineText",
		"id": "fldx9NuchslbT2SgY2",
		"name": "Status"
	},
];

export const defaultMappingOptions = {
	"order": {
		"options": [
			{
				"enabled": true,
				"allow_multiple": false,
				"value": "order::order_number",
				"label": "Order #",
				"supported_sources": [
					"autoNumber",
					"barcode.type",
					"barcode.text",
					"count",
					"createdBy.id",
					"createdBy.email",
					"createdBy.name",
					"currency",
					"date",
					"dateTime",
					"duration",
					"email",
					"externalSyncSource",
					"lastModifiedBy.id",
					"lastModifiedBy.email",
					"lastModifiedBy.name",
					"lastModifiedTime",
					"multipleCollaborators.id",
					"multipleCollaborators.email",
					"multipleCollaborators.name",
					"multipleRecordLinks",
					"multipleSelects",
					"multilineText",
					"number",
					"percent",
					"phoneNumber",
					"rating",
					"richText",
					"rollup",
					"singleCollaborator.id",
					"singleCollaborator.email",
					"singleCollaborator.name",
					"singleLineText",
					"singleSelect",
					"url"
				]
			},

		],
		"label": "Orders"
	},
	"status": {
		"options": [
			{
				"enabled": true,
				"allow_multiple": false,
				"value": "order::status",
				"label": "Status",
				"supported_sources": [
					"autoNumber",
					"barcode.type",
					"barcode.text",
					"count",
					"createdBy.id",
					"createdBy.email",
					"createdBy.name",
					"currency",
					"date",
					"dateTime",
					"duration",
					"email",
					"externalSyncSource",
					"lastModifiedBy.id",
					"lastModifiedBy.email",
					"lastModifiedBy.name",
					"lastModifiedTime",
					"multipleCollaborators.id",
					"multipleCollaborators.email",
					"multipleCollaborators.name",
					"multipleRecordLinks",
					"multipleSelects",
					"multilineText",
					"number",
					"percent",
					"phoneNumber",
					"rating",
					"richText",
					"rollup",
					"singleCollaborator.id",
					"singleCollaborator.email",
					"singleCollaborator.name",
					"singleLineText",
					"singleSelect",
					"url"
				]
			},

		],
		"label": "Status"
	},

};

const featuresByProductType = {
	"orders": {
		"order": [
			"order_number",
			"date_created",
			"status",
			"total",
			"email",
			"billing_first_name",
			"billing_last_name",
			"billing_company",
			"billing_address_1",
			"billing_address_2",
			"billing_city",
			"billing_postcode",
			"billing_country",
			"billing_state",
			"billing_email",
			"billing_phone",
			"payment_method_title",
			"transaction_id",
			"shipping_first_name",
			"shipping_last_name",
			"shipping_company",
			"shipping_address_1",
			"shipping_address_2",
			"shipping_city",
			"shipping_postcode",
			"shipping_country",
			"shipping_country",
			"shipping_phone",
			"customer_note"
		],
	},
};

export const isOptionAvailable = function (value) {
	let available = false;

	const productType = 'orders';

	const group = value.substring(0, value.indexOf('::'));
	const feature = value.substring(value.indexOf('::') + 2);

	// Check if feature is available for product type
	if (featuresByProductType.hasOwnProperty(productType) && featuresByProductType[productType].hasOwnProperty(group) && Array.isArray(featuresByProductType[productType][group])) {
		available = featuresByProductType[productType][group].indexOf(feature) > -1;
	}

	return available;
};

export const mappingAuto = {
	'order::order_number': 'fldx9NuchslbT2SgY',
	'order::status': 'fldx9NuchslbT2SgY2',
};
export const mappingInit = {
	"orders": [
		{
			"groupName": "Order",
			"mappings": [
				{
					"wordpress": "order::order_number",
					"airtableFieldName": "Order #",
				},
				{
					"wordpress": "order::status",
					"airtableFieldName": "Status",
				},
			]
		},
	],
};

export const formOptions ={
	"scheduledSyncTypes": [
		{
			"value": "manual",
			"label": "Manual",
			"enabled": true
		},
		{
			"value": "order_state_change",
			"label": "Automatic *",
			"enabled": true
		},
	],
};
