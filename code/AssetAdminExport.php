<?php

class AssetAdminExport extends LeftAndMainExtension {

	private static $allowed_actions = array(
		'backup'
	);

	public function updateEditForm(Form $form) {
		$backupButton = new LiteralField(
			'BackupButton',
			sprintf(
				'<a class="ss-ui-button ss-ui-action ui-button-text-icon-primary" data-icon="arrow-circle-135-left" title="%s" href="%s">%s</a>',
				'Performs an asset backup in ZIP format. Useful if you want all assets and have no FTP access',
				$this->owner->Link('backup'),
				'Backup files'
			)
		);

		// too specific - only takes a change in field structure to break this
		$form->Fields()
			->findOrMakeTab('Root.ListView')
			->FieldList()
			->first()
			->FieldList()
			->first()
			->FieldList()
			->push($backupButton);

		return $form;
	}

	public function backup() {
		$name = 'assets_' . SS_DateTime::now()->Format('Y-m-d') . '.zip';
		$tmpName = TEMP_FOLDER . '/' . $name;
		$zip = new ZipArchive();

		if(!$zip->open($tmpName, ZIPARCHIVE::OVERWRITE)) {
			user_error('Asset Export Extension: Unable to read/write temporary zip archive', E_USER_ERROR);
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				ASSETS_PATH,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		);

		foreach($files as $file) {
			$local = str_replace(ASSETS_PATH . '/', '', $file);
			$zip->addFile($file, $local);
		}

		if(!$zip->status == ZIPARCHIVE::ER_OK) {
			user_error('Asset Export Extension: ZipArchive returned an error other than OK', E_USER_ERROR);
			return;
		}

		$zip->close();

		ob_flush(); // fix browser crash(?)

		$content = file_get_contents($tmpName);
		unlink($tmpName);

		return SS_HTTPRequest::send_file($content, $name);
	}

}