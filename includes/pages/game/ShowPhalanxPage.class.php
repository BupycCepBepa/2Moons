<?php

/**
 *  2Moons
 *  Copyright (C) 2012 Jan Kröpke
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package 2Moons
 * @author Jan Kröpke <info@2moons.cc>
 * @copyright 2012 Jan Kröpke <info@2moons.cc>
 * @license http://www.gnu.org/licenses/gpl.html GNU GPLv3 License
 * @version 1.7.2 (2013-03-18)
 * @info $Id$
 * @link http://2moons.cc/
 */


class ShowPhalanxPage extends AbstractPage
{
	public static $requireModule = MODULE_PHALANX;
	
	static function allowPhalanx($toGalaxy, $toSystem)
	{
		global $PLANET, $resource;

		if ($PLANET['galaxy'] != $toGalaxy || $PLANET[$resource[42]] == 0 || !isModulAvalible(MODULE_PHALANX) || $PLANET[$resource[903]] < PHALANX_DEUTERIUM) {
			return false;
		}
		
		$PhRange	= self::GetPhalanxRange($PLANET[$resource[42]]);
		$systemMin  = max(1, $PLANET['system'] - $PhRange);
		$systemMax  = $PLANET['system'] + $PhRange;
		
		return $toSystem >= $systemMin && $toSystem <= $systemMax;
	}

	static function GetPhalanxRange($PhalanxLevel)
	{
		return ($PhalanxLevel == 1) ? 1 : pow($PhalanxLevel, 2) - 1;
	}
	
	function __construct() {
		
	}
	
	function show()
	{
		global $PLANET, $LNG, $resource;

		$this->initTemplate();
		$this->setWindow('popup');
		$this->tplObj->loadscript('phalanx.js');
		
		$Galaxy 			= HTTP::_GP('galaxy', 0);
		$System 			= HTTP::_GP('system', 0);
		$Planet 			= HTTP::_GP('planet', 0);
		
		if(!$this->allowPhalanx($Galaxy, $System))
		{
			$this->printMessage($LNG['px_out_of_range']);
		}
		
		if ($PLANET[$resource[903]] < PHALANX_DEUTERIUM)
		{
			$this->printMessage($LNG['px_no_deuterium']);
		}

		$db = Database::get();
		$sql = "UPDATE %%PLANETS%% SET deuterium = deuterium - :phalanxDeuterium WHERE id = :planetID;";
		$db->update($sql, array(
			':phalanxDeuterium'	=> PHALANX_DEUTERIUM,
			':planetID'			=> $PLANET['id']
		));

		$sql = "SELECT id, name, id_owner FROM %%PLANETS%% WHERE universe = :universe
		AND galaxy = :galaxy AND system = :system AND planet = :planet AND :type;";
		
		$TargetInfo = $db->selectSingle($sql, array(
			':universe'	=> Universe::current(),
			':galaxy'	=> $Galaxy,
			':system'	=> $System,
			':planet'	=> $Planet,
			':type'		=> 1
		));

		if(empty($TargetInfo))
		{
			$this->printMessage($LNG['px_out_of_range']);
		}
		
		require 'includes/classes/class.FlyingFleetsTable.php';

		$fleetTableObj = new FlyingFleetsTable;
		$fleetTableObj->setPhalanxMode();
		$fleetTableObj->setUser($TargetInfo['id_owner']);
		$fleetTableObj->setPlanet($TargetInfo['id']);
		$fleetTable	=  $fleetTableObj->renderTable();
		
		$this->assign(array(
			'galaxy'  		=> $Galaxy,
			'system'  		=> $System,
			'planet'   		=> $Planet,
			'name'    		=> $TargetInfo['name'],
			'fleetTable'	=> $fleetTable,
		));
		
		$this->display('page.phalanx.default.tpl');			
	}
}