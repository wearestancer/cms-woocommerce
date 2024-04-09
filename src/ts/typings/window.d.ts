import type { StancerSettings } from "./stancer";
declare global {
  interface AdminData{
    confirmMessage: string;
    descriptionMessage: string;
    minSize: number;
    maxSize: number;
    renewalDescriptionMessage: string;
  }

  interface BlockParams {
    message?: string | null;
  }

  interface StancerData {
    changePaymentMethod?: ChangePaymentMethod
    checkout_url: string;
    initiate: string;
  }
  interface ChangePaymentMethod {
    nonce: string;
    url:string;
  }
  interface WooCommerceCheckoutParams {
    checkout_url: string;
  }

  interface WooCommerceEnvironment {
    wcBlocksRegistry:{
      registerPaymentMethod:(arg0: object)=>void
    }
    wcSettings: WCSettings
  }
  type PaymentMethods = {stancer:StancerSettings}
  interface React{}
  interface WordPressEnvironment{
    htmlEntities:{
      decodeEntities:(arg0:string)=>string
    }
    apiFetch:({})=>Promise<CheckoutResponse>;
  }

  interface CheckoutResponse{
    payment_result:
     {
      payment_status: string;
      redirect_url: string;

    }
  }
  interface WCSettings{
    currency: {
      code: string;
    }
    paymentMethodData: PaymentMethods;
    description: string;
  }
  interface Window {
    stancer_admin: AdminData;
    stancer: StancerData;
    wc : WooCommerceEnvironment;
    wc_checkout_params: WooCommerceCheckoutParams;
    wp: WordPressEnvironment;
    wcSettings :WCSettings;
  }

  const stancer: StancerData;
  const wc_checkout_params: WooCommerceCheckoutParams;

  interface Redirection {
    receipt: string;
  }
}

export {};
