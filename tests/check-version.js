#!/usr/bin/env node

/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );

const rootDir = path.resolve( __dirname, '..' );
const semverPattern = /^\d+\.\d+\.\d+$/;

function readFile( fileName ) {
	return fs.readFileSync( path.join( rootDir, fileName ), 'utf8' );
}

function readJson( fileName ) {
	return JSON.parse( readFile( fileName ) );
}

function addCheck( checks, file, item, actual, expected ) {
	checks.push( {
		file,
		item,
		expected,
		actual: actual === undefined ? '(missing)' : actual,
	} );
}

function matchValue( content, pattern ) {
	const match = content.match( pattern );
	return match ? match[ 1 ].trim() : undefined;
}

const packageJson = readJson( 'package.json' );
const expectedVersion = packageJson.version;
const failures = [];

if ( ! semverPattern.test( expectedVersion ) ) {
	addCheck( failures, 'package.json', 'version', expectedVersion, 'x.y.z' );
}

const packageLock = readJson( 'package-lock.json' );
const pluginFile = readFile( 'beastfeedbacks.php' );
const readme = readFile( 'readme.txt' );

const checks = [
	{
		file: 'package-lock.json',
		item: 'version',
		actual: packageLock.version,
	},
	{
		file: 'package-lock.json',
		item: 'packages[""].version',
		actual:
			packageLock.packages && packageLock.packages[ '' ]
				? packageLock.packages[ '' ].version
				: undefined,
	},
	{
		file: 'beastfeedbacks.php',
		item: 'plugin header Version',
		actual: matchValue( pluginFile, /^\s*\*\s*Version:\s*([^\n]+)$/m ),
	},
	{
		file: 'beastfeedbacks.php',
		item: 'BEASTFEEDBACKS_VERSION',
		actual: matchValue(
			pluginFile,
			/define\(\s*['"]BEASTFEEDBACKS_VERSION['"]\s*,\s*['"]([^'"]+)['"]\s*\)/
		),
	},
	{
		file: 'readme.txt',
		item: 'Stable tag',
		actual: matchValue( readme, /^Stable tag:\s*([^\n]+)$/m ),
	},
];

for ( const check of checks ) {
	if ( ! check.actual ) {
		addCheck(
			failures,
			check.file,
			check.item,
			undefined,
			expectedVersion
		);
		continue;
	}

	if ( ! semverPattern.test( check.actual ) ) {
		addCheck(
			failures,
			check.file,
			check.item,
			check.actual,
			expectedVersion
		);
		continue;
	}

	if ( check.actual !== expectedVersion ) {
		addCheck(
			failures,
			check.file,
			check.item,
			check.actual,
			expectedVersion
		);
	}
}

if ( failures.length > 0 ) {
	console.error( 'Version check failed:' );
	for ( const failure of failures ) {
		console.error(
			`- ${ failure.file } ${ failure.item }: expected ${ failure.expected }, actual ${ failure.actual }`
		);
	}
	process.exit( 1 );
}

console.log( `Version check passed: ${ expectedVersion }` );
