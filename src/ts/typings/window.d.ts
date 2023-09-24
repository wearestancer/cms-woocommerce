import { type Select2Plugin } from 'select2';

declare global {
  interface BlockParams {
    message?: string | null;
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
    wc_checkout_params: WooCommerceCheckoutParams;
  }

  const wc_checkout_params: WooCommerceCheckoutParams;
}

export {};
