import SharedFieldControls from '../components/field-controls';

const TAG_TYPES = [ 'radio', 'checkbox', 'select' ];

export default function FieldControls( props ) {
	return <SharedFieldControls { ...props } tagTypes={ TAG_TYPES } />;
}
