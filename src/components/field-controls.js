import { __ } from '@wordpress/i18n';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	ButtonGroup,
	PanelBody,
	ToggleControl,
	ToolbarGroup,
	ToolbarButton,
	ToolbarDropdownMenu,
} from '@wordpress/components';

function WidthPanel( { selectedWidth, setAttributes } ) {
	function handleChange( newWidth ) {
		const width = selectedWidth === newWidth ? undefined : newWidth;

		setAttributes( { width } );
	}

	return (
		<PanelBody title={ __( 'Width settings' ) }>
			<ButtonGroup aria-label={ __( 'Button width' ) }>
				{ [ 25, 50, 75, 100 ].map( ( widthValue ) => {
					return (
						<Button
							key={ widthValue }
							size="small"
							variant={
								widthValue === selectedWidth
									? 'primary'
									: undefined
							}
							onClick={ () => handleChange( widthValue ) }
						>
							{ widthValue }%
						</Button>
					);
				} ) }
			</ButtonGroup>
		</PanelBody>
	);
}

export default function FieldControls( {
	attributes,
	setAttributes,
	tagTypes = [],
} ) {
	const { width } = attributes;

	return (
		<>
			<InspectorControls>
				<WidthPanel
					selectedWidth={ width }
					setAttributes={ setAttributes }
				/>

				<PanelBody title={ __( 'Field settings', 'beastfeedbacks' ) }>
					<ToggleControl
						label={ __( 'Required input', 'beastfeedbacks' ) }
						className="label__required"
						checked={ attributes.required }
						onChange={ ( value ) =>
							setAttributes( { required: value } )
						}
						help={ __(
							'You can edit the "required" label in the editor',
							'beastfeedbacks'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						label={ attributes.tagType }
						text={ attributes.tagType }
						icon={ null }
						controls={ tagTypes.map( ( targetTagType ) => {
							const isActive =
								targetTagType === attributes.tagType;

							return {
								title: targetTagType,
								isActive,
								onClick() {
									setAttributes( {
										tagType: targetTagType,
									} );
								},
								role: 'menuitemradio',
							};
						} ) }
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						title={ __( 'Required', 'beastfeedbacks' ) }
						label={ __( 'Required', 'beastfeedbacks' ) }
						text={ __( 'Required', 'beastfeedbacks' ) }
						icon={ null }
						onClick={ () => {
							setAttributes( {
								required: ! attributes.required,
							} );
						} }
						className={
							attributes.required ? 'is-pressed' : undefined
						}
					/>
				</ToolbarGroup>
			</BlockControls>
		</>
	);
}
