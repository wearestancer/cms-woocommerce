export interface StancerData {
  changePaymentMethod?: {
    nonce: string;
    url: string;
  };
  initiate: string;
}

export interface StancerSettings {
  description: string;
  label: string;
  logo: {
    url: string;
    class: string;
  };
  page_type: string;
  title: string;
}


