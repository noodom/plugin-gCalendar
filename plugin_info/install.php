<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Définie le chemin des fichiers caches pour gCalendar //
if (!defined('GCALENDAR_CACHE_PATH')) define('GCALENDAR_CACHE_PATH', dirname(__FILE__) . '/../../../tmp/gCalendar/');


function gCalendar_install() {
    $cron = cron::byClassAndFunction('gCalendar', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('gCalendar');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setTimeout(1);
        $cron->setSchedule('* * * * *');
        $cron->save();
    }
	// crée le répertoire d'installation //
	if (!file_exists(GCALENDAR_CACHE_PATH)) {
		if (mkdir(GCALENDAR_CACHE_PATH)===true) log::add('gCalendar','info','gCalendar_install(): Le répertoire ('.GCALENDAR_CACHE_PATH.') vient d\'être créé.');
			else log::add('gCalendar','error','gCalendar_install(): Impossible de créer le répertoire: '.GCALENDAR_CACHE_PATH);
	}
}

function gCalendar_update() {
    $cron = cron::byClassAndFunction('gCalendar', 'pull');
    if (!is_object($cron)) {
        $cron = new cron();
    }
    $cron->setClass('gCalendar');
    $cron->setFunction('pull');
    $cron->setEnable(1);
    $cron->setTimeout(1);
    $cron->setSchedule('* * * * *');
    $cron->save();
    $cron->stop();
}

function gCalendar_remove() {
    $cron = cron::byClassAndFunction('gCalendar', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }
	// supprimer les fichiers cache et le répertoire //
	array_map('unlink', glob(GCALENDAR_CACHE_PATH."gCalendar_*.tmp.xml"));
	if (rmdir(GCALENDAR_CACHE_PATH)===true) log::add('gCalendar','info','gCalendar_remove(): Le répertoire ('.GCALENDAR_CACHE_PATH.') et ses fichiers ont correctement été supprimés.');
		else log::add('gCalendar','error','gCalendar_remove(): Impossible de supprimer le répertoire: '.GCALENDAR_CACHE_PATH);
}

?>
