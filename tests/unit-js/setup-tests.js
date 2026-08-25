/* global jest */
import '@testing-library/jest-dom';

// モック: @wordpress/deprecated (ButtonGroup等の非推奨警告を抑制)
jest.mock( '@wordpress/deprecated', () => jest.fn() );

// モック: @wordpress/blocks
jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
	createBlock: jest.fn(),
	getBlockTypes: jest.fn( () => [] ),
} ) );

// モック: @wordpress/block-editor
jest.mock( '@wordpress/block-editor', () => {
	return {
		useBlockProps: Object.assign(
			jest.fn( ( props = {} ) => ( {
				'data-testid': 'mock-block-props',
				...props,
			} ) ),
			{
				save: jest.fn( ( props = {} ) => ( {
					'data-testid': 'mock-block-props-save',
					...props,
				} ) ),
			}
		),
		useInnerBlocksProps: Object.assign(
			jest.fn( ( props = {}, options = {} ) => ( {
				'data-testid': 'mock-inner-blocks-props',
				'data-options': JSON.stringify( options ),
				...props,
			} ) ),
			{
				save: jest.fn( ( props = {} ) => ( {
					'data-testid': 'mock-inner-blocks-props-save',
					...props,
				} ) ),
			}
		),
		InnerBlocks: Object.assign(
			( { template, allowedBlocks } ) => (
				<div
					data-testid="mock-inner-blocks"
					data-template={ JSON.stringify( template ) }
					data-allowed-blocks={ JSON.stringify( allowedBlocks ) }
				/>
			),
			{
				Content: () => <div data-testid="mock-inner-blocks-content" />,
			}
		),
		InspectorControls: ( { children } ) => (
			<div data-testid="mock-inspector-controls">{ children }</div>
		),
		BlockControls: ( { children } ) => (
			<div data-testid="mock-block-controls">{ children }</div>
		),
		RichText: Object.assign(
			( {
				value,
				onChange,
				onSplit,
				onRemove,
				placeholder,
				className,
				tagName = 'div',
			} ) => {
				const Tag = tagName;
				return (
					<Tag
						className={ className }
						data-testid="mock-rich-text"
						data-value={ value }
						onClick={ () =>
							onChange && onChange( 'Updated Value' )
						}
					>
						{ value || placeholder }
						<textarea
							data-testid="mock-rich-text-input"
							value={ value || '' }
							onChange={ ( e ) =>
								onChange && onChange( e.target.value )
							}
						/>
						{ onSplit && (
							<button
								data-testid="mock-rich-text-split"
								onClick={ ( e ) => {
									e.stopPropagation();
									onSplit( value || '', true );
								} }
							>
								Split
							</button>
						) }
						{ onRemove && (
							<button
								data-testid="mock-rich-text-remove"
								onClick={ ( e ) => {
									e.stopPropagation();
									onRemove();
								} }
							>
								Remove
							</button>
						) }
					</Tag>
				);
			},
			{
				Content: ( { value, tagName = 'div' } ) => {
					const Tag = tagName;
					return (
						<Tag data-testid="mock-rich-text-content">
							{ value }
						</Tag>
					);
				},
			}
		),
	};
} );
