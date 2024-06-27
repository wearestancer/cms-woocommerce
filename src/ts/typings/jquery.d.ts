import { type Select2Plugin } from 'select2';

declare global {

  interface JQuery<TElement = HTMLElement> {
    block: (params?: BlockParams) => this;
    selectWoo: Select2Plugin<TElement>;
    unblock: () => this;
  }

  interface JQueryStatic {
    scroll_to_notices: (element: JQuery) => this;
  }
}
export { };
