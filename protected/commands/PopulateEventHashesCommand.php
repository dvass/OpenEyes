<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

class PopulateEventHashesCommand extends CConsoleCommand {
	public function run($args) {
		if (!isset(Yii::app()->params['sync_node_id'])) {
			throw new Exception("Fatal: sync_node_id isn't set.");
		}

		$server_id = Yii::app()->params['sync_node_id'];

		$hashes = array();

		foreach (Event::model()->findAll() as $event) {
			$hash = sha1(rand());

			while (in_array($hash,$hashes)) {
				$hash = sha1(rand());
			}

			$hashes[] = $hash;

			Yii::app()->db->createCommand("update event set hash='$server_id-$hash' where id = {$event->id}")->query();

			echo ".";
		}

		echo "\n";
	}
}