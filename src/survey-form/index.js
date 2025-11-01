import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
} from '@wordpress/block-editor';

import variations from './variations';
import metadata from './block.json';
import './style.scss';

/**
 * アンケートフォーム
 */
registerBlockType( metadata.name, {
	/**
	 * @see https://developer.wordpress.org/resource/dashicons/#feedback
	 */
	icon: 'feedback',

	variations,

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
		const innerBlocksProps = useInnerBlocksProps(
			{},
			{
				// デフォルトの入れ子ブロック、variationsで上書きされる
				template: [
					[
						'core/heading',
						{
							level: 3,
							content: __(
								'Please tell us what your customers have to say',
								'beastfeedbacks'
							),
						},
					],
					[
						'beastfeedbacks/survey-choice',
						{
							label: __(
								'Satisfaction with this site',
								'beastfeedbacks'
							),
							tagType: 'radio',
							required: true,
							items: [
								__( 'Very satisfied', 'beastfeedbacks' ),
								__( 'Satisfaction', 'beastfeedbacks' ),
								__( 'Normal', 'beastfeedbacks' ),
								__( 'Dissatisfaction', 'beastfeedbacks' ),
								__( 'Very dissatisfied', 'beastfeedbacks' ),
							],
						},
					],
					[
						'beastfeedbacks/survey-input',
						{
							label: __( 'Description', 'beastfeedbacks' ),
							tagType: 'textarea',
						},
					],
					[
						'core/button',
						{
							text: __( 'Submit', 'beastfeedbacks' ),
							tagName: 'button',
							type: 'submit',
						},
					],
				],
			}
		);

		return (
			<div { ...blockProps }>
				<form name="beastfeedbacks_survey_form">
					<div { ...innerBlocksProps } />
				</form>
			</div>
		);
	},
} );
