module.exports = {
    root: true,

    env: {
        node: true,
    },

    ignorePatterns: [
        'node_modules',
        'resources/css',
        'resources/lambda',
        'resources/views',
        '**/*.js',
    ],

    extends: [
        'plugin:@typescript-eslint/recommended',
        'plugin:@typescript-eslint/recommended-requiring-type-checking',
        'plugin:vue/vue3-recommended',
        '@vue/standard',
        '@vue/typescript/recommended',
    ],

    plugins: [
        'vue',
    ],

    parserOptions: {
        ecmaVersion: 2020,
        sourceType: 'module',
        project: ['./tsconfig.json'],
        parser: '@typescript-eslint/parser',
    },


    overrides: [
        {
            files: [
                '*.ts',
            ],
            parser: '@typescript-eslint/parser',
        },
    ],

    rules: {
        // https://eslint.org/docs/rules/
        'no-return-assign': 'off',
        'no-console': 'error',
        'no-debugger': 'error',
        'semi': 'off',
        'indent': 'off',
        'no-void': 'off',
        'no-unused-expressions': 'off',
        'space-before-function-paren': [
            'error', {
                'anonymous': 'never',
                'named': 'never',
                'asyncArrow': 'always',
            },
        ],
        'no-trailing-spaces': 'warn',
        'no-any': 'off',
        'no-prototype-builtins': 'off',
        'no-unused-vars': 'off',
        'prefer-rest-params': 'warn',
        'no-extra-parens': 'off',
        'quotes': 'off',
        'func-call-spacing': 'off',
        'camelcase': 'off',
        'comma-spacing': 'off',
        'keyword-spacing': 'off',
        'object-curly-spacing': ['warn', 'always'],
        'comma-dangle': ['warn', 'always-multiline'],
        'max-len': ['warn', 120],
        'eqeqeq': 'error',
        'lines-between-class-members': 'off',
        'no-use-before-define': 'off',
        'no-multi-spaces': [
            'error',
            {
                exceptions: {
                    VariableDeclarator: true,
                    AssignmentExpression: true,
                },
            },
        ],

        // https://github.com/typescript-eslint/typescript-eslint/tree/master/packages/eslint-plugin#supported-rules
        '@typescript-eslint/indent': ['warn', 4],
        '@typescript-eslint/semi': 'error',
        '@typescript-eslint/no-unused-expressions': 'error',
        '@typescript-eslint/quotes': ['warn', 'single'],
        '@typescript-eslint/no-extra-parens': [
            'error',
            'all',
            {
                'nestedBinaryExpressions': false,
                'enforceForArrowConditionals': false,
            },
        ],
        '@typescript-eslint/no-unused-vars': 'warn',
        '@typescript-eslint/no-useless-constructor': 'warn',
        '@typescript-eslint/no-explicit-any': 'off',
        '@typescript-eslint/ban-ts-comment': 'off',
        '@typescript-eslint/no-unsafe-return': 'off',
        '@typescript-eslint/no-unsafe-assignment': 'off',
        '@typescript-eslint/explicit-module-boundary-types': 'off',
        '@typescript-eslint/prefer-nullish-coalescing': 'warn',
        '@typescript-eslint/prefer-optional-chain': 'warn',
        '@typescript-eslint/prefer-ts-expect-error': 'warn',
        '@typescript-eslint/promise-function-async': 'error',
        '@typescript-eslint/func-call-spacing': ['error', 'never'],
        '@typescript-eslint/comma-spacing': 'warn',
        '@typescript-eslint/keyword-spacing': 'warn',
        '@typescript-eslint/consistent-indexed-object-style': ['error', 'record'],
        '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],
        '@typescript-eslint/member-delimiter-style': 'warn',
        '@typescript-eslint/type-annotation-spacing': 'warn',
        '@typescript-eslint/naming-convention': [
            'error',
            {
                selector: 'default',
                format: ['camelCase'],
            },
            {
                selector: 'objectLiteralProperty',
                format: ['camelCase', 'PascalCase'],
            },
            {
                selector: 'typeLike',
                format: ['PascalCase'],
            },
            {
                selector: 'parameter',
                format: null,
                filter: {
                    regex: '^_.*',
                    match: true,
                },
            },
        ],
        '@typescript-eslint/no-non-null-assertion': 'off',
        '@typescript-eslint/lines-between-class-members': 'off',
        '@typescript-eslint/no-use-before-define': 'error',

        // https://eslint.vuejs.org/rules/
        // if unsure and eslint doesn't cover it please refer to https://v3.vuejs.org/style-guide/
        'vue/html-indent': ['warn', 4],
        'vue/component-name-in-template-casing': ['warn', 'PascalCase'],
        'vue/match-component-file-name': [
            'error', {
                extensions: ['jsx', 'vue'],
            },
        ],
        'vue/new-line-between-multi-line-property': 'warn',
        'vue/max-attributes-per-line': [
            'warn', {
                'singleline': 3,
                'multiline': 1,
            },
        ],
        'vue/first-attribute-linebreak': [
            'warn', {
                'singleline': 'ignore',
                'multiline': 'beside',
            },
        ],
        'vue/no-boolean-default': ['error', 'default-false'],
        'vue/no-duplicate-attr-inheritance': 'error',
        'vue/no-empty-component-block': 'warn',
        'vue/no-multiple-objects-in-class': 'error',
        'vue/no-potential-component-option-typo': [
            'error', {
                presets: ['vue', 'vue-router'],
            },
        ],
        'vue/no-reserved-component-names': [
            'error', {
                'disallowVueBuiltInComponents': true,
                'disallowVue3BuiltInComponents': true,
            },
        ],
        'vue/no-template-target-blank': 'error',
        'vue/no-unsupported-features': [
            'error', {
                'version': '^3.4.19',
            },
        ],
        'vue/no-useless-mustaches': 'warn',
        'vue/no-useless-v-bind': 'error',
        'vue/padding-line-between-blocks': 'warn',
        'vue/require-name-property': 'error',
        'vue/v-for-delimiter-style': 'error',
        'vue/v-on-event-hyphenation': 'error',
        'vue/v-on-function-call': [
            'error', 'never', {
                'ignoreIncludesComment': true,
            },
        ],
        'vue/eqeqeq': 'error',
        'vue/no-extra-parens': 'warn',
        'vue/html-closing-bracket-newline': [
            'error', {
                'singleline': 'never',
                'multiline': 'never',
            },
        ],
        'vue/script-setup-uses-vars': 'off', // enable if using setup syntax,
        'vue/multi-word-component-names': 'off',

        'vue/html-self-closing': [
            'error', {
                'html': {
                    'void': 'any',
                    'normal': 'always',
                    'component': 'always',
                },
                'svg': 'always',
                'math': 'always',
            },
        ],
    },
};
