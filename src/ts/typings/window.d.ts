import { type Select2Plugin } from 'select2';

declare global {
  interface BlockParams {
    message?: string | null;
  }

  interface StancerData {
    changePaymentMethod?: {
      nonce: string;
      url: string;
    };
    initiate: string;
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
    stancer: StancerData;
    wc_checkout_params: WooCommerceCheckoutParams;
  }

  const stancer: StancerData;
  const wc_checkout_params: WooCommerceCheckoutParams;
}

export {};
