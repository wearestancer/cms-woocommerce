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
import type { BillingDataProps, ShippingDataProps, ShippingStatusProps, BillingAddressShippingAddress } from "./payment-data";

declare global {

  interface BlockApiResponse extends PaymentResult {
    payment_result: PaymentResult;
  }
  // Create a data type with the data structured as needed by the wc API for a POST checkout
  interface BlockParams {
    message?: string | null;
  }
  interface CompletePaymentData extends BillingAddressShippingAddress {
    payment_method: 'stancer';
  }

  interface CheckoutStatusProps {
    // If true then totals are being calculated in the checkout.
    isCalculating: boolean;
    // If true then the checkout has completed it's processing.
    isComplete: boolean;
    // If true then the checkout is idle (no  activity happening).
    isIdle: boolean;
    // If true then checkout is processing (finalizing) the order with the server.
    isProcessing: boolean;
  }

  interface PaymentResult {
    payment_status: string;
    redirect_url: string;
    payment_details: Array<{ key: string, value: string }>;
  }

  interface StancerPaymentInterface {
    billing?: BillingDataProps;
    shippingData?: ShippingDataProps;
    activePaymentMethod?: string;
  }
  type SubmitType = (data: ListenerData, callback?: () => boolean) => void;
  // This TypeDef is incomplete please refers to woocommerce doc for the complete typedef
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
    // Deprecated. For setting an error (error message string) for express payment methods.
    //  Does not change payment status.
    setExpressPaymentError: (errorMessage?: string) => void;
    // Various data related to shipping.
    shippingData: ShippingDataProps;
    // Various shipping status helpers.
    shippingStatus: ShippingStatusProps;
    // A boolean which indicates whether the shopper has checked the save payment method checkbox.
    shouldSavePayment: boolean;
  };

}
export { };
