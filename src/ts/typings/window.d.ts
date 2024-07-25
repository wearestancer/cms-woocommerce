import { type Select2Plugin } from 'select2';

declare global {
  interface AdminData{
    confirmMessage: string;
    descriptionVariables: string[];
    minSize:number;
    maxSize:number;
  }

  interface BlockParams {
    message?: string | null;
  }

  interface StancerData {
    changePaymentMethod?: ChangePaymentMethod
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

  interface Window {
    stancer_admin: AdminData;
    stancer: StancerData;
    wc_checkout_params: WooCommerceCheckoutParams;
  }

  const stancer: StancerData;
  const wc_checkout_params: WooCommerceCheckoutParams;

  interface Redirection {
    receipt: string;
  }
}

export {};
