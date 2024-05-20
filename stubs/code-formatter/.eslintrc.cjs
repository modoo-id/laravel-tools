module.exports = {
  env: {
    browser: true,
    node: true,
    es2021: true,
    "vue/setup-compiler-macros": true,
  },
  extends: ["plugin:vue/vue3-essential", "plugin:prettier/recommended", "eslint:recommended"],
  parserOptions: {
    ecmaVersion: "latest",
    sourceType: "module",
  },
  plugins: ["vue", "prettier", "import"],
  rules: {
    "prettier/prettier": "error",
    "vue/multi-word-component-names": 0,
  },
};
