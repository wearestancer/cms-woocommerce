import { type Select2Plugin } from 'select2';

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

  interface JQuery<TElement = HTMLElement> {
    block: (params?: BlockParams) => this;
    selectWoo: Select2Plugin<TElement>;
    unblock: () => this;
  }

  interface JQueryStatic {
    scroll_to_notices: (element: JQuery) => this;
  }

  interface StancerSettings {
    description: string;
    label: string;
    logo:{
      url: string;
      class: string;
    }
    page_type: string;
    title: string;

  }

  interface WooCommerceEnvironment {
    wcBlocksRegistry:{
      registerPaymentMethod:(arg0: object)=>void
    }
    wcSettings:{
      CURRENCY: {
        code: string;
      }
      getPaymentMethodData: (arg0: string, arg1: object)=>StancerSettings,
      description: string;
    }
  }

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

  interface Window {
    stancer_admin: AdminData;
    stancer: StancerData;
    wc : WooCommerceEnvironment;
    wc_checkout_params: WooCommerceCheckoutParams;
    wp: WordPressEnvironment;
  }

  const stancer: StancerData;
  const wc_checkout_params: WooCommerceCheckoutParams;

  interface Redirection {
    receipt: string;
  }
}

export {};
