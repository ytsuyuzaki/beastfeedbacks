import SharedFieldControls from '../components/field-controls';

const TAG_TYPES = [ 'text', 'textarea', 'email', 'url', 'number' ];

export default function FieldControls( props ) {
	return <SharedFieldControls { ...props } tagTypes={ TAG_TYPES } />;
}
