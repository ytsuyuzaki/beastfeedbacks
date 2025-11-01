import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import metadata from './block.json';

import './style.scss';

const TEMPLATE = [
	[
		'core/button',
		{
			text: __( 'Like', 'beastfeedbacks' ),
			tagName: 'button',
			type: 'submit',
		},
	],
];

/**
 * Likeボタン
 */
registerBlockType( metadata.name, {

	save: () => {
		const blockProps = useBlockProps.save();

		return (
			<div {...blockProps}>
				<InnerBlocks.Content />
			</div>
		);
	},

	edit: () => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<form name="beastfeedbacks_like_form">
					<div className="beastfeedbacks-like_balloon">
						<p className="like-count">0</p>
					</div>
					<InnerBlocks
						allowedBlocks={ TEMPLATE }
						template={ TEMPLATE }
						templateLock="all"
					/>
				</form>
			</div>
		);
	},
} );
