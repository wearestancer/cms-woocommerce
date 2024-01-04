/**
 * Those type are a mix between the types defined in the woocommerce codesource :
 * See here for a description of those types
 * https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#props-fed-to-payment-method-nodes
 * and here for the complete definition of those
 * https://github.com/woocommerce/woocommerce-blocks/blob/trunk/assets/js/types/type-defs/payment-method-interface.ts
 *
 * There is also some custom defined types
 *
 */

declare global {

  //Create a data type with the data structured as needed by the wc API for a POST checkout
  interface CompletePaymentData extends BillingAddressShippingAddress{
    payment_method:string;
  }
  //Type of both addresses needed for the ApiPaymentData
   interface BillingAddressShippingAddress {
  	billing_address: Partial< CartBillingAddress >;
  	shipping_address: Partial< CartShippingAddress >;
  }

  // Types of the addresses fields for billing & shipping addresses
   interface CartShippingAddress extends BaseAddress, FirstNameLastName {
  	company: string;
  	phone: string;
  }

   interface CartBillingAddress extends CartShippingAddress {
  	email: string;
  }
   interface FirstNameLastName {
  	first_name: string;
  	last_name: string;
  }

  interface BaseAddress {
  	address_1: string;
  	address_2: string;
  	city: string;
  	state: string;
  	postcode: string;
  	country: string;
  }

  export interface ShippingStatusProps {
  	// Current error status for shipping.
  	shippingErrorStatus: {
  		// Whether the status is pristine.
  		isPristine: boolean;
  		// Whether the status is valid.
  		isValid: boolean;
  		// Whether the address is invalid.
  		hasInvalidAddress: boolean;
  		// Whether an error has happened.
  		hasError: boolean;
  	};
  }

  export interface ShippingDataProps {
  	// True when rates are being selected.
  	isSelectingRate: boolean;
  	// True if cart requires shipping.
  	needsShipping: boolean;
  	// An object containing package IDs as the key and selected rate as the value (rate ids).
  	selectedRates: Record< string, unknown >;
  	// A function for setting selected rates (receives id).
  	setSelectedRates: (
  		newShippingRateId: string,
  		packageId: string | number
  	) => unknown;
  	// The current set shipping address.
  	shippingAddress: CartShippingAddress;
  	// Whether the rates are loading or not.
  	shippingRatesLoading: boolean;
  }

  export interface BillingDataProps {
  	// The address used for billing.
  	billingAddress: CartBillingAddress;
  	billingData: CartBillingAddress;
  	// The customer Id the order belongs to.
  	customerId: number;
  	// True means that the site is configured to display prices including tax.
  	displayPricesIncludingTax: boolean;
  }

  export interface CheckoutStatusProps {
  	// If true then totals are being calculated in the checkout.
  	isCalculating: boolean;
  	// If true then the checkout has completed it's processing.
  	isComplete: boolean;
  	// If true then the checkout is idle (no  activity happening).
  	isIdle: boolean;
  	// If true then checkout is processing (finalizing) the order with the server.
  	isProcessing: boolean;
  }

  //this Type is the only props needed for our ReactComponents, expand types
  type StancerPaymentInterface = {
    billing: BillingDataProps;
    shippingData: ShippingDataProps;
    activePaymentMethod: string;
  }


  //This TypeDef is incomplete please refers to woocommerce doc for the complete typedef
   type PaymentMethodInterface = {
  	// Indicates what the active payment method is.
  	activePaymentMethod: string;
  	// Various billing data items.
  	billing: BillingDataProps;
  	// The current checkout status exposed as various boolean state.
  	checkoutStatus: CheckoutStatusProps;
  	// Used to trigger checkout processing.
  	onSubmit: () => void;
  	// Various payment status helpers.
  	paymentStatus: {
  		hasError: boolean;
  		hasFailed: boolean;
  		isDoingExpressPayment: boolean;
  		isFinished: boolean;
  		isIdle: boolean;
  		isPristine: boolean;
  		isProcessing: boolean;
  		isStarted: boolean;
  		isSuccessful: boolean;
  	};
  	// Deprecated. For setting an error (error message string) for express payment methods. Does not change payment status.
  	setExpressPaymentError: ( errorMessage?: string ) => void;
  	// Various data related to shipping.
  	shippingData: ShippingDataProps;
  	// Various shipping status helpers.
  	shippingStatus: ShippingStatusProps;
  	// A boolean which indicates whether the shopper has checked the save payment method checkbox.
  	shouldSavePayment: boolean;
  };


}
export{};

