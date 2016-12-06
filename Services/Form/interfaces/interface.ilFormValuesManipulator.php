<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package        Services/Form
 */
interface ilFormValuesManipulator
{
	/**
	 * @param array $values
	 * @return array $values
	 */
	public function manipulateFormSubmitValues($values);
}