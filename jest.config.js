const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	setupFilesAfterEnv: [
		...( defaultConfig.setupFilesAfterEnv || [] ),
		'<rootDir>/tests/unit-js/setup-tests.js',
	],
	testMatch: [
		'**/__tests__/**/*.[jt]s?(x)',
		'**/?(*.)+(spec|test).[jt]s?(x)',
		'!**/tests/e2e/**',
	],
	transform: {
		'\\.[jt]sx?$|\\.mjs$': require.resolve(
			'@wordpress/scripts/config/babel-transform'
		),
	},
	transformIgnorePatterns: [ 'node_modules/(?!(@wordpress|uuid|marked)/)' ],
	moduleNameMapper: {
		...( defaultConfig.moduleNameMapper || {} ),
		'^react$': require.resolve( 'react' ),
		'^react/(.*)$': '<rootDir>/node_modules/react/$1',
		'^react-dom$': require.resolve( 'react-dom' ),
		'^react-dom/(.*)$': '<rootDir>/node_modules/react-dom/$1',
		'^@wordpress/element$': require.resolve( '@wordpress/element' ),
	},
};
