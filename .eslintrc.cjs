module.exports = {
  root: true,
  env: {
    browser: true,
    es6: true,
    es2020: true,
  },
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: "module",
  },
  globals: {
    wp: "readonly",
  },
  ignorePatterns: [
    "node_modules/",
    "vendor/",
    "**/*.min.js",
  ],
  extends: [
    "eslint:recommended",
  ],
  rules: {
    "no-eval": "error",
    "no-implied-eval": "error",
    "no-alert": "warn",
    "no-console": [
      "warn",
      { "allow": ["warn", "error"] }
    ],
    "no-unused-vars": [
      "warn",
      { "args": "none", "ignoreRestSiblings": true }
    ],
  },
  overrides: [
    {
      files: ["build/**/*.js"],
      rules: {
        "no-undef": "off"
      }
    }
  ]
};