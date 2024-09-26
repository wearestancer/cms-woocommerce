
const ERROR = 2;
const OFF = 0;
const WARN = 1;

module.exports = {
  root: true,
  env: {
    browser: true,
  },
  extends: [
    'plugin:@typescript-eslint/recommended',
  ],
  ignorePatterns: ['vendor/*', 'public/*', 'scripts/*'],
  overrides: [
    {
      files: [
        '**.js',
      ],
      rules: {
        '@typescript-eslint/no-var-requires': OFF,
      },
    },
    {
      files: [
        '**.ts',
        '**.tsx',
      ],
      parser: '@typescript-eslint/parser',
      plugins: [
        '@typescript-eslint',
      ],
    },
  ],

  parserOptions: {
    ecmaVersion: 2020,
  },
  rules: {
    '@typescript-eslint/func-call-spacing': ERROR,
    '@typescript-eslint/no-inferrable-types': OFF,
    '@typescript-eslint/no-namespace': OFF,
    'comma-dangle': [
      ERROR,
      'always-multiline',
    ],
    'func-call-spacing': OFF,
    'no-console': process.env.NODE_ENV === 'production' ? WARN : OFF,
    'no-debugger': process.env.NODE_ENV === 'production' ? WARN : OFF,
    quotes: [
      ERROR,
      'single',
      {
        allowTemplateLiterals: true,
        avoidEscape: true,
      },
    ],
    semi: [
      ERROR,
      'always',
    ],
    'space-before-function-paren': [
      ERROR,
      {
        anonymous: 'always',
        asyncArrow: 'always',
        named: 'never',
      },
    ],
  },
};
