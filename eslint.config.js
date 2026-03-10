const { defineConfig, globalIgnores } = require("eslint/config");

const globals = require("globals");
const tsParser = require("@typescript-eslint/parser");
const typescriptEslint = require("@typescript-eslint/eslint-plugin");
const stylistic = require("@stylistic/eslint-plugin");
const js = require("@eslint/js");

const { FlatCompat } = require("@eslint/eslintrc");

const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all,
});
const ERROR = 2;
const OFF = 0;
const WARN = 1;

module.exports = defineConfig([
  {
    plugins: {
      "@stylistic": stylistic,
    },
    languageOptions: {
      globals: {
        ...globals.browser,
      },

      ecmaVersion: 2020,
      parserOptions: {},
    },

    extends: compat.extends("plugin:@typescript-eslint/recommended"),

    rules: {
      "@typescript-eslint/no-inferrable-types": OFF,
      "@typescript-eslint/no-namespace": OFF,
      "comma-dangle": [ERROR, "always-multiline"],
      "func-call-spacing": OFF,
      "no-console": process.env.NODE_ENV === "production" ? WARN : OFF,
      "no-debugger": process.env.NODE_ENV === "production" ? WARN : OFF,

      semi: [ERROR, "always"],

      "space-before-function-paren": [
        ERROR,
        {
          anonymous: "always",
          asyncArrow: "always",
          named: "never",
        },
      ],
    },
  },
  globalIgnores(["vendor/*", "public/*", "vendor-prefixer/*", "scripts/*"]),
  {
    files: ["**/**.js"],

    rules: {
      "@typescript-eslint/no-var-requires": OFF,
    },
  },
  {
    files: ["**/**.ts", "**/**.tsx"],

    languageOptions: {
      parser: tsParser,
    },

    plugins: {
      "@typescript-eslint": typescriptEslint,
    },
  },
]);
