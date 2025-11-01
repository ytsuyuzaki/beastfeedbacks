import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import metadata from './block.json';

import './style.scss';

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 3,
			content: __(
				'Were you satisfied with the content of the article?',
				'beastfeedbacks'
			),
		},
	],
	[
		'core/buttons',
		{},
		[
			[
				'core/button',
				{
					text: __( 'Yes', 'beastfeedbacks' ),
					tagName: 'button',
					type: 'submit',
				},
			],
			[
				'core/button',
				{
					text: __( 'No', 'beastfeedbacks' ),
					tagName: 'button',
					type: 'submit',
				},
			],
		],
	],
];

/**
 * 投票ボタン
 */
registerBlockType( metadata.name, {
	save: () => {
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},

	edit: () => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<form name="beastfeedbacks_vote_form">
					<InnerBlocks template={ TEMPLATE } templateLock={ false } />
				</form>
			</div>
		);
	},
} );
