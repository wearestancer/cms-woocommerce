const prefix = 'woocommerce_stancer';
type Keys = HTMLInputElement[]
const testMode = document.getElementById(prefix +'_test_mode') as HTMLInputElement;
const form = document.getElementById('mainform') as HTMLFormElement;
const testKeys: Keys = [
  document.getElementById(prefix +'_api_test_public_key') as HTMLInputElement,
  document.getElementById(prefix +'_api_test_secret_key')as HTMLInputElement,
];
const liveKeys: Keys = [
  document.getElementById(prefix +'_api_live_public_key')as HTMLInputElement,
  document.getElementById(prefix +'_api_live_secret_key')as HTMLInputElement,
];


testMode?.addEventListener('input',() => testMode.checked ? unRequireKey(liveKeys) : requireKey(liveKeys));
const requireKey = (keys :Keys)=>{
  keys.map((key)=>key.setAttribute('required','required'));
};
const unRequireKey = (keys :Keys)=>{
  keys.map((key)=>key.removeAttribute('required'));
};
