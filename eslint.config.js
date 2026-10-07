import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import vue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';

export default tseslint.config(
    {
        ignores: [
            'node_modules',
            'resources/css',
            'resources/lambda',
            'resources/views',
            '**/*.js',
        ],
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...vue.configs['flat/recommended'],
    {
        files: ['**/*.vue', '**/*.ts'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                ecmaVersion: 2020,
                sourceType: 'module',
                parser: tseslint.parser,
            },
            globals: {
                route: 'readonly',
                console: 'readonly',
            },
        },
        plugins: {
            vue,
        },
        rules: {
            // Basic rules
            'no-console': ['error', { allow: ['error', 'warn'] }],
            'no-debugger': 'error',
            'no-trailing-spaces': 'warn',
            'object-curly-spacing': ['warn', 'always'],
            'comma-dangle': ['warn', 'always-multiline'],
            'max-len': ['warn', 120],
            'eqeqeq': 'error',

            // Vue rules
            'vue/html-indent': ['warn', 4],
            'vue/component-name-in-template-casing': ['warn', 'PascalCase'],
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
            'vue/html-closing-bracket-newline': [
                'error', {
                    'singleline': 'never',
                    'multiline': 'never',
                },
            ],
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
            'vue/multi-word-component-names': 'off',
        },
    },
    {
        files: ['*.ts'],
        languageOptions: {
            parser: tseslint.parser,
        },
    },
);
