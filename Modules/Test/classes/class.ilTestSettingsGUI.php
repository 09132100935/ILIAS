<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * GUI class that manages the editing of general test settings/properties
 * shown on "general" subtab
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.ilObjTestSettingsGeneralGUI.php 57702 2015-01-31 21:30:34Z bheyser $
 *
 * @package		Modules/Test
 */
abstract class ilTestSettingsGUI
{
	/**
	 * @var ilObjTest $testOBJ
	 */
	protected $testOBJ = null;

	/**
	 * object instance for currently active settings template
	 *
	 * @var $settingsTemplate ilSettingsTemplate
	 */
	protected $settingsTemplate = null;

	public function __construct(ilObjTest $testOBJ)
	{
		$this->testOBJ = $testOBJ;

		$templateId = $this->testOBJ->getTemplate();

		if( $templateId )
		{
			include_once "Services/Administration/classes/class.ilSettingsTemplate.php";
			$this->settingsTemplate = new ilSettingsTemplate($templateId, ilObjAssessmentFolderGUI::getSettingsTemplateConfig());
		}
	}

	protected function isHiddenFormItem($formFieldId)
	{
		if( !$this->settingsTemplate )
		{
			return false;
		}

		$settings = $this->settingsTemplate->getSettings();

		if( !isset($settings[$formFieldId]) )
		{
			return false;
		}

		if( !$settings[$formFieldId]['hide'] )
		{
			return false;
		}

		return true;
	}

	protected function formPropertyExists(ilPropertyFormGUI $form, $propertyId)
	{
		return $form->getItemByPostVar($propertyId) instanceof ilFormPropertyGUI;
	}
}