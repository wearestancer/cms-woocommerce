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

  const stancer_data: StancerData;
  const wc_checkout_params: WooCommerceCheckoutParams;

  type CheckoutResponse = CheckoutResponseFailure | CheckoutResponseSuccess
  type PaymentMethods = { stancer: StancerSettings }

  interface ChangePaymentData {
    nonce?: string;
    subscription: string;
    action: 'information' | 'initiate' | 'validate';
  }
  interface ChangePaymentMethod {
    nonce: string;
    url: string;
  }

  interface CheckoutResponseBase {
    card?: string;
    messages?: string;
    refresh: boolean;
    reload: boolean;
  }

  interface CheckoutResponseFailure extends CheckoutResponseBase {
    result: 'failure';
    reason: string
  }

  interface CheckoutResponseReact {
    redirect: string
    result: string
    receipt: string
  }

  interface CheckoutResponseSuccess extends CheckoutResponseBase {
    order_id: number | string;
    receipt: string;
    redirect: string;
    result: 'success';
  }

  interface ListenerData extends ListenerDataBase {
    button: string;
  }

  interface ListenerDataBase {
    route: {
      url?: string;
      path: string;
    }
    data: ChangePaymentData | (() => string) | string;
  }

  interface MessageData {
    height: number;
    status: 'error' | 'finished' | 'init' | 'secure-auth-end' | 'secure-auth-error' | 'secure-auth-start';
    url: string & Location;
    width: number;
  }

  interface React { }

  interface Redirection {
    receipt: string;
  }

  interface ServerCallData extends ListenerDataBase {
    responseCallBack: (arg0: CheckoutResponse) => void;
  }

  interface StancerData {
    changePaymentMethod?: ChangePaymentMethod
    initiate: string;
  }

  interface stancerWindows {
    stancer_iframe: (result: CheckoutResponseSuccess | CheckoutResponseReact) => void;
    stancer_onSubmit: (arg0: ListenerData, arg1?: () => boolean) => void;
    stancer_changePaymentMethodOrCallback: (arg0: () => void) => () => void;
    stancer_callServer: ({ data, route, responseCallBack }: ServerCallData) => void;
    stancer_paymentMethodHasBeenChanged: (arg0: MessageData) => boolean;
  }

  interface WCSettings {
    currency: {
      code: string;
    }
    paymentMethodData: PaymentMethods;
    description: string;
  }

  interface WordPressEnvironment {
    htmlEntities: {
      decodeEntities: (arg0: string) => string
    }
    apiFetch(arg0:{
      path: string;
      method:string;
      data: ChangePaymentData | (() => string) | string;
    }):Promise<CheckoutResponse>;
    apiFetch(arg0: {
      path: '/wc/store/v1/checkout',
      method: 'POST',
      data: CompletePaymentData,
    }):Promise<BlockApiResponse>;
  }

  interface WooCommerceCheckoutParams {
    checkout_url: string;
  }

  interface WooCommerceEnvironment {
    wcBlocksRegistry: {
      registerPaymentMethod: (arg0: object) => void
    }
    wcSettings: WCSettings
  }
  interface Window extends stancerWindows {
    stancer_admin: AdminData;
    stancer: StancerData;
    wc : WooCommerceEnvironment;
    wc_checkout_params: WooCommerceCheckoutParams;
    wp: WordPressEnvironment;
    wcSettings: WCSettings;
  }
}

export { };
