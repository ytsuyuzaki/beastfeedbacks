import { render, screen, fireEvent } from '@testing-library/react';
import EditListBlock from '../edit-list-block';

describe( 'EditListBlock component', () => {
	const defaultAttributes = {
		tagType: 'radio',
		items: [ 'Option 1', 'Option 2', 'Option 3' ],
	};

	it( 'renders initial choices list correctly', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		expect( screen.getAllByText( 'Option 1' )[ 0 ] ).toBeInTheDocument();
		expect( screen.getAllByText( 'Option 2' )[ 0 ] ).toBeInTheDocument();
		expect( screen.getAllByText( 'Option 3' )[ 0 ] ).toBeInTheDocument();
		expect( screen.getAllByRole( 'radio' ) ).toHaveLength( 3 );
	} );

	it( 'renders select dummy item when tagType is select and not selected', () => {
		const setAttributes = jest.fn();
		const { container } = render(
			<EditListBlock
				attributes={ { ...defaultAttributes, tagType: 'select' } }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		expect( screen.getByText( 'Please select' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Option 1' ) ).not.toBeInTheDocument();
		expect(
			container.querySelectorAll( '.beastfeedbacks-survey-choice_item' )
		).toHaveLength( 1 );
	} );

	it( 'renders select items when tagType is select and block is selected', () => {
		const setAttributes = jest.fn();
		const { container } = render(
			<EditListBlock
				attributes={ { ...defaultAttributes, tagType: 'select' } }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		expect( screen.getByText( 'Please select' ) ).toBeInTheDocument();
		expect( screen.getAllByText( 'Option 1' )[ 0 ] ).toBeInTheDocument();
		expect( screen.getAllByText( 'Option 2' )[ 0 ] ).toBeInTheDocument();
		expect( screen.getAllByText( 'Option 3' )[ 0 ] ).toBeInTheDocument();
		expect(
			container.querySelectorAll( '.beastfeedbacks-survey-choice_item' )
		).toHaveLength( 4 );
	} );

	it( 'updates single choice label when text is changed', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const inputs = screen.getAllByTestId( 'mock-rich-text-input' );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'Updated Option 2' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			items: [ 'Option 1', 'Updated Option 2', 'Option 3' ],
		} );
	} );

	it( 'splits items into multiple items when newlines are present', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const inputs = screen.getAllByTestId( 'mock-rich-text-input' );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'New Option 2A\nNew Option 2B' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			items: [ 'Option 1', 'New Option 2A', 'New Option 2B', 'Option 3' ],
		} );
	} );

	it( 'adds a new choice item when split action is triggered', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const splitButtons = screen.getAllByTestId( 'mock-rich-text-split' );
		fireEvent.click( splitButtons[ 0 ] );

		expect( setAttributes ).toHaveBeenCalledWith( {
			items: [ 'Option 1', '', 'Option 2', 'Option 3' ],
		} );
	} );

	it( 'deletes a choice item when remove action is triggered', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const removeButtons = screen.getAllByTestId( 'mock-rich-text-remove' );
		fireEvent.click( removeButtons[ 1 ] );

		expect( setAttributes ).toHaveBeenCalledWith( {
			items: [ 'Option 1', 'Option 3' ],
		} );
	} );

	it( 'does not delete item if there is only one choice remaining', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ { ...defaultAttributes, items: [ 'Only Item' ] } }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const removeButtons = screen.getAllByTestId( 'mock-rich-text-remove' );
		fireEvent.click( removeButtons[ 0 ] );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'passes custom style prop to wrapper element', () => {
		const setAttributes = jest.fn();
		const style = { margin: '10px', display: 'flex' };
		const { container } = render(
			<EditListBlock
				style={ style }
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		const wrapper = container.querySelector(
			'.beastfeedbacks-survey-choice_items'
		);
		expect( wrapper ).toHaveStyle( { margin: '10px', display: 'flex' } );
	} );

	it( 'does not update items when text is changed to empty or whitespace-only', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const inputs = screen.getAllByTestId( 'mock-rich-text-input' );
		fireEvent.change( inputs[ 0 ], {
			target: { value: '' },
		} );
		fireEvent.change( inputs[ 0 ], {
			target: { value: '   \n  \n' },
		} );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'does not split item when isOriginal is false', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const splitFalseButtons = screen.getAllByTestId(
			'mock-rich-text-split-false'
		);
		fireEvent.click( splitFalseButtons[ 0 ] );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'does not split item when both value and split value are empty', () => {
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ { ...defaultAttributes, items: [ '' ] } }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const splitButtons = screen.getAllByTestId( 'mock-rich-text-split' );
		fireEvent.click( splitButtons[ 0 ] );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'handles focus management with setFocus timers and cursor selection', () => {
		jest.useFakeTimers();
		const setAttributes = jest.fn();
		render(
			<EditListBlock
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ true }
			/>
		);

		const inputs = screen.getAllByTestId( 'mock-rich-text-input' );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'Updated Option 2' },
		} );

		jest.runAllTimers();

		const removeButtons = screen.getAllByTestId( 'mock-rich-text-remove' );
		fireEvent.click( removeButtons[ 2 ] );

		jest.runAllTimers();

		expect( setAttributes ).toHaveBeenCalledWith( {
			items: [ 'Option 1', 'Option 2' ],
		} );

		jest.useRealTimers();
	} );
} );
