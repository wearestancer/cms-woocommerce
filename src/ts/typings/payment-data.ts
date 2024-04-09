// Must be declared first as they are used in other interfaces
interface BaseAddress {
  address_1: string;
  address_2: string;
  city: string;
  country: string;
  postcode: string;
  state: string;
}

interface FirstNameLastName {
  first_name: string;
  last_name: string;
}

export interface ShippingStatusProps {
  // Current error status for shipping.
  shippingErrorStatus: {
    // Whether an error has happened.
    hasError: boolean;
    // Whether the address is invalid.
    hasInvalidAddress: boolean;
    // Whether the status is pristine.
    isPristine: boolean;
    // Whether the status is valid.
    isValid: boolean;
  };
}


// Must be declared second as they are used in other interfaces but use first interfaces.

// Types of the addresses fields for billing & shipping addresses
interface CartShippingAddress extends BaseAddress, FirstNameLastName {
  company: string;
  phone: string;
}

// Must be declare third as it use the interface declared second

interface CartBillingAddress extends CartShippingAddress {
  email: string;
}
export interface ShippingDataProps {
  // True when rates are being selected.
  isSelectingRate: boolean;
  // True if cart requires shipping.
  needsShipping: boolean;
  // An object containing package IDs as the key and selected rate as the value (rate ids).
  selectedRates: Record<string, unknown>;
  // A function for setting selected rates (receives id).
  setSelectedRates: (newShippingRateId: string, packageId: string | number) => unknown;
  // The current set shipping address.
  shippingAddress: CartShippingAddress;
  // Whether the rates are loading or not.
  shippingRatesLoading: boolean;
}

// Must be declared fourth as it use the CartBillingAddress & CartShippingAddress

// Type of both addresses needed for the ApiPaymentData
export interface BillingAddressShippingAddress {
  billing_address: Partial<CartBillingAddress>;
  shipping_address: Partial<CartShippingAddress>;
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

// Must be declared fifth as it use the interface declared fourth.
