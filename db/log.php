<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Definition of log events
 *
 * @package    mod
 * @subpackage groupreg
 * @copyright  2011 Olexandr Savchuk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'groupreg', 'action'=>'view', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'update', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'add', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'assign', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'resetassign', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'finalize', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'report', 'mtable'=>'groupreg', 'field'=>'name'),
	array('module'=>'groupreg', 'action'=>'report download', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'choose', 'mtable'=>'groupreg', 'field'=>'name'),
    array('module'=>'groupreg', 'action'=>'choose again', 'mtable'=>'groupreg', 'field'=>'name'),
);