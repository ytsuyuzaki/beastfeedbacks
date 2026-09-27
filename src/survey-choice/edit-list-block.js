import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

const noop = () => {};

const setFocus = ( wrapper, selector, index, cursorToEnd ) => {
	setTimeout( () => {
		const input = wrapper.querySelectorAll( selector )[ index ];
		if ( ! input ) {
			return;
		}

		input.focus();

		// 全削除
		if ( document.createRange && cursorToEnd ) {
			const range = document.createRange();
			range.selectNodeContents( input );
			range.collapse( false );
			const selection = document.defaultView.getSelection();
			selection.removeAllRanges();
			selection.addRange( range );
		}
	}, 0 );
};

export default function EditListBlock( {
	style,
	attributes,
	setAttributes,
	isSelected,
} ) {
	const setItems = ( items ) => setAttributes( { items } );
	const { items, tagType } = attributes;

	const itemsRef = useRef();
	const changeFocus = ( index, cursorToEnd ) =>
		setFocus( itemsRef.current, '[role=textbox]', index, cursorToEnd );

	const handleSingleValue = ( index, value ) => {
		const _items = [ ...items ];
		_items[ index ] = value;

		setItems( _items );
		changeFocus( index );
	};

	const handleMultiValues = ( index, array ) => {
		const _items = [ ...items ];
		const cursorToEnd = array[ array.length - 1 ] !== '';

		if ( _items[ index ] ) {
			_items[ index ] = array.shift();
			index++;
		}

		_items.splice( index, 0, ...array );

		setItems( _items );
		changeFocus( index + array.length - 1, cursorToEnd );
	};

	const handleChange = ( index ) => ( value ) => {
		const values = ( value || '' )
			.split( '\n' )
			.filter( ( op ) => op && op.trim() !== '' );

		if ( ! values.length ) {
			return;
		}

		if ( values.length > 1 ) {
			handleMultiValues( index, values );
		} else {
			handleSingleValue( index, values.pop() );
		}
	};

	const handleSplit = ( index ) => ( value, isOriginal ) => {
		if ( ! isOriginal ) {
			return;
		}

		const splitValue = items[ index ].slice( value.length );

		if ( ! value && ! splitValue ) {
			return;
		}

		handleMultiValues( index, [ value, splitValue ] );
	};

	const handleDelete = ( index ) => () => {
		if ( items.length === 1 ) {
			return;
		}

		const _items = [ ...items ];
		_items.splice( index, 1 );
		setItems( _items );
		changeFocus( Math.max( index - 1, 0 ), true );
	};

	return (
		<div
			ref={ itemsRef }
			className="beastfeedbacks-survey-choice_items"
			style={ style }
		>
			{ 'select' === tagType && (
				<div className="beastfeedbacks-survey-choice_item select_wrap">
					<div className="dummy-select">
						{ __( 'Please select', 'beastfeedbacks' ) }
					</div>
				</div>
			) }

			{ ( 'select' !== tagType || isSelected ) &&
				items.map( ( value, index ) => (
					<div
						className="beastfeedbacks-survey-choice_item"
						key={ index }
					>
						{ 'select' === tagType ? (
							'・'
						) : (
							<input type={ tagType } />
						) }
						<RichText
							tagName="label"
							value={ value }
							onChange={ handleChange( index ) }
							onSplit={ handleSplit( index ) }
							onRemove={ handleDelete( index ) }
							onReplace={ noop }
							placeholder={ __( 'Add item', 'beastfeedbacks' ) }
							__unstableDisableFormats
						/>
					</div>
				) ) }
		</div>
	);
}
