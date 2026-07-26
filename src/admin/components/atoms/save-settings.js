/* WordPress */
import { __ } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';

/* Library */

/*Atrc*/
import { AtrcButtonSaveTemplate1, AtrcFooterTemplate1 } from 'atrc';

/* Inbuilt */
import { AtrcReduxContextData } from '../../routes';

/*Local*/
const SaveSettings = ({ onClick }) => {
	const data = useContext(AtrcReduxContextData);
	const { dbIsLoading, dbCanSave, dbSettings, dbSaveSettings } = data;

	return (
		<AtrcFooterTemplate1 useDynamicPosition={true}>
			<AtrcButtonSaveTemplate1
				isLoading={dbIsLoading}
				canSave={dbCanSave}
				text={{
					saved: __('Saved', 'mock-exam-engine'),
					save: __('Save settings', 'mock-exam-engine'),
					saving: __('Saving', 'mock-exam-engine'),
				}}
				disabled={dbIsLoading || !dbCanSave}
				onClick={() => dbSaveSettings(dbSettings)}
			/>
		</AtrcFooterTemplate1>
	);
};

export default SaveSettings;
