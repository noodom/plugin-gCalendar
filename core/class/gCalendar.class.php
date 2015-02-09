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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

// Définie le chemin des fichiers caches pour gCalendar //
if (!defined('GCALENDAR_CACHE_PATH')) define('GCALENDAR_CACHE_PATH', '/tmp/gCalendar/');
// Définie les minutes de rafraichissement du fichier cache (mettre le 1er temps toujours en 1ère position et donc le 00 à la fin) //
if (!defined('GCALENDAR_REFRESH_TIME')) define('GCALENDAR_REFRESH_TIME', '15;30;45;00');


/**
 * Class Extend eqLogic pour le plugin gCalendar
 * @author jeedom 
 */
class gCalendar extends eqLogic {
    /*     * *************************Attributs****************************** */
    protected $_sRefreshDate;


    /*     * ***********************Methode static*************************** */

	/**
	 * Fonction d'execution principale appelée via CRON, lance l'excution de la commande et la mise à jour du widget
	 * @return void
	 */
    public static function pull() {
		log::add('gCalendar','debug','[START PULL]===== pull().nb gCal='.count(self::byType('gCalendar')));
        foreach (self::byType('gCalendar') as $gCalendar) {
			$_bDoRefresh=FALSE; // mettre TRUE pour forcer la remise du cache du widget //
			log::add('gCalendar','debug','['.$gCalendar->getId().'] pull().isEnable='.$gCalendar->getIsEnable().' - isVisible='.$gCalendar->getIsVisible());
            // Action si widget actif //
			if ($gCalendar->getIsEnable()) {
				log::add('gCalendar','debug','['.$gCalendar->getId().'] pull().nb cmd='.count($gCalendar->getCmd('info')));
				foreach ($gCalendar->getCmd('info') as $cmd) {
					$value = $cmd->execute();
					//log::add('gCalendar','debug','['.$gCalendar->getId().'] pull().value='.$value);
					//log::add('gCalendar','debug','['.$gCalendar->getId().'] pull().execCmd()='.$cmd->execCmd());
					if ($value != $cmd->execCmd()) {
						log::add('gCalendar','info','['.$gCalendar->getId().'|'.$cmd->getId().'] pull() Refresh Data : do event()');
						$cmd->setCollectDate(''); //date('Y-m-d H:i:s')
						$cmd->event($value);
						$_bDoRefresh=true;
					}
					//log::add('gCalendar','debug','['.$gCalendar->getId().'] pull().execCmd() after='.$cmd->execCmd());
					//log::add('gCalendar','debug','['.$gCalendar->getId().'] pull().cmd objet='.print_r($cmd, true));
				}
				if (($gCalendar->getIsVisible()) && (($_bDoRefresh)||(date('H:i')==='00:00'))) {
					log::add('gCalendar','debug','['.$gCalendar->getId().'] pull() remove cache and refreshWidget ...');
					$mc = cache::byKey('gcalendarWidgetmobile' . $gCalendar->getId());
					$mc->remove();
					$mc = cache::byKey('gcalendarWidgetdashboard' . $gCalendar->getId());
					$mc->remove();
					$gCalendar->_sRefreshDate = date('Y-m-d H:i:s'); //je rajoute cette action, car la fonction getCollectDate() ne me retourne rien (?) //
					$gCalendar->toHtml('mobile');
					$gCalendar->toHtml('dashboard');
					$gCalendar->refreshWidget();
				}
			}
		}
		log::add('gCalendar','debug','[END PULL]=====');
    }
	
	/**
	 * Format le Widget "gCalendar"
	 * @return void
	 */
 	public function toHtml($_version) {
		log::add('gCalendar','debug','['.$this->getId().'] toHtml('.$_version.') start ...');
		$_version = jeedom::versionAlias($_version);
        $mc = cache::byKey('gcalendarWidget' . $_version . $this->getId());
        if ($mc->getValue() != '') {
			log::add('gCalendar','debug','['.$this->getId().'] toHtml('.$_version.') aborded !');
            return $mc->getValue();
        }
		$replace = array(
			'#id#' => $this->getId(),
			'#name#' => ($this->getIsEnable()) ? $this->getName() : '<del>' . $this->getName() . '</del>',
			'#background_color#' => $this->getBackgroundColor($_version),
			'#eqLink#' => $this->getLinkToConfiguration(),
			'#refreshDate#' => (!empty($this->_sRefreshDate))?$this->_sRefreshDate:date('Y-m-d H:i:s'),
			'#today#' => ($_version=='mobile')?date('d'):date('j M Y (\SW)'),
			'#gCalArray#' => '0',
		);
		// pour chaque calendrier du widget //
		// 0:nom jeedom / 1:type de vue / 2:date de mise à jour / 3:valeur affichée / 4:titre google / 5:url / 6:nb évènement //
		$nbCalRefresh=0;
        foreach ($this->getCmd('info') as $cmdGCal) {
			if ($cmdGCal->getIsVisible()) {
				if (($_sEvents=$cmdGCal->execCmd())!='') {
					if ($cmdGCal->getConfiguration('viewStyle')!='current_titleOnly') {
						$_aEvents=explode('||',$_sEvents);
						$nbEvent = ($cmdGCal->getConfiguration('defaultValue','Aucun')!=$_sEvents)?count($_aEvents):0;
						if ($nbEvent>0) {
							for($i=0;$i<count($_aEvents);$i++) {
								if ($_version=='mobile') { 
									if (date('Hi')<str_replace(":","",substr($_aEvents[$i],7,5))) { 
										$_aEvents[$i]="<div style='font-size:13px;'>".$_aEvents[$i]."</div>"; 
									} else {
										$_aEvents[$i]= '';
										$nbEvent--;
									}
								} else { 
									$_sBorder='border-bottom:1px solid #DDDDDD;'; 
									if(date('Hi')>=str_replace(":","",substr($_aEvents[$i],7,5))) { 
										$_aEvents[$i]="<div style='font-style:italic;font-size:12px;color:#BBBBBB;".$_sBorder."'>".$_aEvents[$i]."</div>"; 
									} else { 
										$_aEvents[$i]="<div style='font-style:none;font-size:12px;color:#666666;".$_sBorder."'>".$_aEvents[$i]."</div>"; 
									}
								}
							}
							$_sEvents=implode('',$_aEvents);
							$_sEvents=str_replace(" [D][A] ", " <i class='fa fa-plus-circle' title='évènement actif: 1ère minute' style='font-size:13px;color:#FF0000;'></i> ",$_sEvents);
							$_sEvents=str_replace(" [F][A] ", " <i class='fa fa-ban' title='évènement actif: dernière minute' style='font-size:13px;color:#FF0000;'></i> ",$_sEvents);
							$_sEvents=str_replace(" [A] ", " <i class='fa fa-check-circle-o' title='évènement actif' style='font-size:13px;color:#0000FF;'></i> ",$_sEvents);
						} else {
							if ($_version=='mobile') { 
								$_sEvents="<div style='font-size:13px;'>".$_sEvents."</div>";
							} else {
								$_sEvents="<span style='font-style:none;font-size:12px;color:#666666;'>".$_sEvents."</span>";
							}
						}
					} else {
						$_aEvents=explode(' - ',$_sEvents);
						$nbEvent = ($cmdGCal->getConfiguration('defaultValue','Aucun')!=$_sEvents)?count($_aEvents):0;
						if ($_version=='mobile') { 
							$_sEvents="<div style='font-size:13px;'>".$_sEvents."</div>";
						} else {
							$_sEvents="<div style='font-style:none;font-size:12px;color:#666666;'>".$_sEvents."</div>";
						}
					}
					$_sEvents= str_replace('"','\"',$_sEvents);
					$replace['#gCalArray#'] .= ',["'.str_replace('"','\"',$cmdGCal->getName()).'", "'.$cmdGCal->getConfiguration('viewStyle').'", "'.$cmdGCal->getCollectDate().'", "'.$_sEvents.'", "'.str_replace('"','\"',$cmdGCal->getConfiguration('_sGCalTitle')).'", "'.str_replace('"','\"',$cmdGCal->getConfiguration('_sGCalUrlCalendar')).'", "'.$nbEvent.'"]';
					$nbCalRefresh++;
				}
			}
		} 
		log::add('gCalendar','debug','['.$this->getId().'] toHtml().replace='.print_r($replace,true));
		$html = template_replace($replace, getTemplate('core', $_version, 'gCalendar','gCalendar'));
		cache::set('gcalendarWidget' . $_version . $this->getId(), $html, 0);
		log::add('gCalendar','info','['.$this->getId().'] toHtml('.$_version.') Refresh Widget ('.$nbCalRefresh.' cal.): OK');
        return $html;
	}

	/**
     * Traitement des actions au moment de l'enregistrement de l'objet plugin
	 * @return void
	 */
    public function preSave() {
		// création du repertoire cache s'il n'existe pas //
        if (!file_exists(GCALENDAR_CACHE_PATH)) {
			if (mkdir(GCALENDAR_CACHE_PATH)===true) {
				log::add('gCalendar','info','['.$this->getId().'] preSave(): Le répertoire ('.GCALENDAR_CACHE_PATH.') vient d\'être créé.');
			} else {
				log::add('gCalendar','error','['.$this->getId().'] preSave(): Le répertoire ('.GCALENDAR_CACHE_PATH.') n\'a pas put être créé.');
				throw new Exception(__('Le répertoire ('.GCALENDAR_CACHE_PATH.') n\'a pas put être créé', __FILE__));
			}
        }
		// suppression pour raffraichissement du widget //
		$mc = cache::byKey('gcalendarWidgetmobile' . $this->getId());
		$mc->remove();
		$mc = cache::byKey('gcalendarWidgetdashboard' . $this->getId());
		$mc->remove();		
	}

	
    /*     * *********************Methode d'instance************************* */
}

/**
 * Class Extend cmd pour le plugin gCalendar
 * @author jeedom 
 */
class gCalendarCmd extends cmd {
    /*     * *************************Attributs****************************** */
	public $_sGCalUpdatedDate = '';
	public $_bRefreshedCache = false;
	
    /*     * ***********************Methode static*************************** */
    

    /*     * *********************Methode d'instance************************* */

	/**
     * Traitement des actions au moment de l'enregistrement de l'objet plugin
	 * @return void
	 */
    public function preSave() {
        if ($this->getConfiguration('calendarUrl') == '') {
            throw new Exception(__('L\'url de l\'agenda ne peut être vide', __FILE__));
        }
		// suppression du fichier pour permettre de regénérer le cache //
		$this->deleteFileCache();
//		if ($this->getIsEnable()) {
			$this->setEventOnly(1);
			$this->event($this->execute());
//		}
    }

	/**
     * Traitement des actions principales (test de refresh cache, génération des données pour le widget, ...)
     * @param array $_options tableau des options de la fonction execute()
	 * @return
	 */
    public function execute($_options = array()) {
		log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] execute() starting... defaultValue="'.$this->getConfiguration('defaultValue').'", viewStyle="'.$this->getConfiguration('viewStyle').'", indicateur="'.$this->getConfiguration('indicDebFin').'"');
		$fileCache = GCALENDAR_CACHE_PATH.$this->getFileCacheName();
		
		try {
			// vérifie si le fichier local existe ou si heure/minute de rafraichissement du cache //
			if ((!file_exists($fileCache)) || (in_array(date('i'),explode(';',GCALENDAR_REFRESH_TIME))==true)){
				log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] execute() Lancement du traitement du fichier/cache ...');
				// définie la période à récupérer //
				/*switch($this->getConfiguration('viewStyle')) {
					case "1day_today": 
						$ts_s=mktime(0,0,0,date('m'),date('d'),date('Y')); $ts_e=mktime(23,59,59,date('m'),date('d'),date('Y')); break;
					case "1day_next1hour": 
						$ts_s=mktime(); $ts_e=strtotime("+1 hours"); break;
					case "current_withHour":
					case "current_titleOnly":
					default: $ts_s=mktime(); $ts_e=strtotime("+".(current(explode(';',GCALENDAR_REFRESH_TIME))+1)." minutes");
				}*/
				$ts_s=mktime(0,0,0,date('m'),date('d'),date('Y')); 
				$ts_e=mktime(23,59,59,date('m'),date('d'),date('Y'));
				log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] execute() Période récupérée:'.gmdate('Y-m-d\TH:i:00',$ts_s).' à '.gmdate('Y-m-d\TH:i:59',$ts_e));
				// définie les options d'entrée du filtre //
				$_optFilter = array(
						'startmin' => gmdate('Y-m-d\TH:i:00', $ts_s),
						'startmax' => gmdate('Y-m-d\TH:i:59', $ts_e),
						'sortorder' => 'ascending',
						'orderby' => 'starttime',
						'maxresults' => '20',
						'startindex' => '1',
						'search' => '',
						'singleevents' => 'true',
						'futureevents' => 'false',
						'timezone' => 'Europe/Paris',
						'showdeleted' => 'false'
					);	
				$this->execRefreshCache($_optFilter);
			}
			// analyse du fichier en cache //
			log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] execute().fileCache='.$fileCache);
            $oAgenda = new JeeGoogleAgenda($fileCache,false,true);
            // récupère les évènements (sans parémètre, car n'utilise pas le filtre dans la class) //
            $aEvents = $oAgenda->getEvents();
			//log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] execute().oAgenda='.print_r($oAgenda,true));
			if ($this->_bRefreshedCache) {
				log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] execute()._sGCalTitle='.$this->getConfiguration('_sGCalTitle','0').' - getTitle()='.$oAgenda->getTitle());
				if ($this->getConfiguration('_sGCalTitle','0') != $oAgenda->getTitle()) {	$this->setConfiguration('_sGCalTitle',$oAgenda->getTitle()); }
				if ($this->getConfiguration('_sGCalUrlCalendar','0') != $oAgenda->getUrlPublic()) { $this->setConfiguration('_sGCalUrlCalendar',$oAgenda->getUrlPublic()); }
			}
			//log::add('gCalendar','debug','execute().aEvents:'.print_r($aEvents,true));
            $result = array();
            foreach ($aEvents as $oEvent) {
				$sEventStartDate = (string) $oEvent->getStartDate();
				$sEventEndDate = (string) $oEvent->getEndDate();
				log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'|'.$oEvent->getTitle().'] execute().sEventStartDate='.$sEventStartDate.', sEventEndDate='.$sEventEndDate);
				if ((!empty($sEventStartDate)) && (!empty($sEventEndDate)))	{
					if (strtotime($sEventStartDate) <= strtotime($sEventEndDate)) {
						$_aFormatedEvent = $this->formatData($oEvent, $sEventStartDate, $sEventEndDate);
						if (isset($_aFormatedEvent['t'])) array_push($result, $_aFormatedEvent);
					} else {
						log::add('gCalendar','info','['.$this->eqLogic_id.'|'.$this->id.'|'.$oEvent->getTitle().'] execute(): la date de début ('.$sEventStartDate.')est supérieure à la date de fin ('.$sEventEndDate.') >> vérifier votre RDV dans l\'agent Google');
					}
				}
			}
			if (count($result) == 0) {
				return __(str_replace("'","\'",$this->getConfiguration('defaultValue', 'Aucun')), __FILE__);
			} else {
				$view='';
				//log::add('gCalendar','debug','execute().result:'.print_r($result,true));
				// Formate les évènements dans une variable affichable //
				for($i=0;$i<count($result);$i++) { 
					if (isset($result[$i]['t'])) {
						$view.=($i!=0)?$result[$i]['s']:''; 
						$view.=(!empty($result[$i]['h']))?'('.$result[$i]['h'].') ':'';
						$view.=(!empty($result[$i]['a']))?$result[$i]['a']:'';
						$view.=$result[$i]['t'];
					}
				}
				return (!empty($view))?$view:$this->getConfiguration('defaultValue','Aucun');
			}
		} catch (GoogleAgendaException $e) {
			if ($this->getConfiguration('defaultValue') != '') {
				log::add('gCalendar', 'debug', '['.$this->eqLogic_id.'|'.$this->id.'] Problème de lecture des données en cache, URL/accès internet invalide.');
			} else {
				throw $e;
			}
		}
		$_sExecCmd = $this->execCmd();
		log::add('gCalendar', 'debug', '['.$this->eqLogic_id.'|'.$this->id.'] Conservation et utilisation des données connues en cache...');
		return (!empty($_sExecCmd))?$_sExecCmd:$this->getConfiguration('defaultValue','Aucun');
	}

	/**
     * Définit le format de la donnée (pour affichage ensuite dans le widget)
     * @param object $oEvent objet évènement de google agenda
     * @param string $sEventStartDate date/heure de début de l'évènement (reformatée)
     * @param string $sEventEndDate date/heure de fin de l'évènement (reformatée)
	 * @return array tableau de résultat, event par event
	 *			t=titre / h=heure au format hh:mm-hh:mm / a=actif ou non (utile pour affichage période) / s=séparateur
	 *				actif = [A] pour dire que l'évènement est en cours
	 *						[D][A] premier minute de l'évènement (et dernière si l'évènement dure 1minute)
	 *						[F][A] dernière minute de l'évènement
	 */
	public function formatData($oEvent, $sEventStartDate, $sEventEndDate) {
		$tsNow = mktime();
		$tsTodayStart = mktime(0,0,0,date('m',$tsNow),date('d',$tsNow),date('Y',$tsNow));
		$tsTodayEnd = mktime(23,59,59,date('m',$tsNow),date('d',$tsNow),date('Y',$tsNow));
		$array = array();
		// redéfinie les heures de début et de fin en fonction de la journée courante (utile pour les évènements sur plusieurs jours) //
		$sNewStart = (strtotime($sEventStartDate) <= $tsTodayStart)?$tsTodayStart:strtotime($sEventStartDate);
		$sNewEnd = (strtotime($sEventEndDate) >= $tsTodayEnd)?$tsTodayEnd:strtotime($sEventEndDate);
		$title = (string)$oEvent->getTitle();
		switch($this->getConfiguration('viewStyle')) {
			case "1day_today":  
				if ( (($sNewStart<=$tsTodayStart) || ($sNewStart<=$tsTodayEnd))
					&& (($tsTodayStart<=$sNewEnd) || ($tsTodayEnd<=$sNewEnd)) ) {
					log::add('gCalendar','debug','['.$oEvent->getTitle().'] execute() add Event - start:'.$sNewStart.' - end:'.$sNewEnd);
					$array = array('t'=>$title,'h'=>date('H:i',$sNewStart).'-'.date('H:i',$sNewEnd),'a'=>$this->setActif($sNewStart,$sNewEnd,strtotime($sEventStartDate),strtotime($sEventEndDate),$tsNow),'s'=>'||');
				}
				break;
			case "1day_next1hour": 
				$timeend=(strtotime("+1 hours")>=$tsTodayEnd)?$tsTodayEnd:strtotime("+1 hours");
				if ( (($sNewStart<=$tsNow) || ($sNewStart<=$timeend))
					&& (($tsNow<=$sNewEnd) || ($timeend<=$sNewEnd)) ) {
					log::add('gCalendar','debug','['.$oEvent->getTitle().'] execute() add Event - start:'.$sNewStart.' - end:'.$sNewEnd);
					$array = array('t'=>$title,'h'=>date('H:i',$sNewStart).'-'.date('H:i',$sNewEnd),'a'=>$this->setActif($sNewStart,$sNewEnd,strtotime($sEventStartDate),strtotime($sEventEndDate),$tsNow),'s'=>'||');
				}
				break;
			case "current_withHour":
				if ( ($sNewStart<=$tsNow) && ($tsNow<=$sNewEnd) ) {
					log::add('gCalendar','debug','['.$oEvent->getTitle().'] execute() add Event - start:'.$sNewStart.' - end:'.$sNewEnd);
					$array = array('t'=>$title,'h'=>date('H:i',$sNewStart).'-'.date('H:i',$sNewEnd),'a'=>$this->setActif($sNewStart,$sNewEnd,strtotime($sEventStartDate),strtotime($sEventEndDate),$tsNow),'s'=>'||');
				}
				break;
			case "current_titleOnly":
			default: 
				if ( ($sNewStart<=$tsNow) && ($tsNow<=$sNewEnd) ) {
					log::add('gCalendar','debug','['.$oEvent->getTitle().'] execute() add Event - start:'.$sNewStart.' - end:'.$sNewEnd);
					$array = array('t'=>$title,'h'=>'','a'=>'','s'=>' - ');
				}
		}
		return $array;
	}

	/**
     * Définie la valeur de l'état "Actif" 
	 * @return string 
	 */
	public function setActif($sNewStart,$sNewEnd,$sEventStartDate,$sEventEndDate,$tsNow) {
		$_sActif =(($sNewStart<=$tsNow)&&($tsNow<=$sNewEnd))?'[A] ':'';
		if ((!empty($_sActif))&&($this->getConfiguration('indicDebFin')==1)) {
			if (date('YzHi',$sEventStartDate)==date('YzHi',$tsNow)) { $_sActif ='[D]'.$_sActif; }
				else {
					if (date('H:i',$sEventEndDate)!='23:59') { $sEventEndDate=$sEventEndDate-60; }
					if ((date('YzHi',$sEventEndDate))==date('YzHi',$tsNow)) { $_sActif ='[F]'.$_sActif; }
				}
		}
		return $_sActif;
	}

	
	/**
     * Récupère le flux GCalendar et le strock dans un fichier cache
     * @param array $_optFilter tableau des options de filtrage
	 * @return void
	 */
	public function execRefreshCache($_optFilter) {
		log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] Rafraichissement du fichier cache ...');
		$_sGCalUrl = $this->getConfiguration('calendarUrl');
		if (!empty($_sGCalUrl)) {
			if (!$this->loadSaveXML($_optFilter, $_sGCalUrl)) {
				log::add('gCalendar','error','['.$this->eqLogic_id.'|'.$this->id.'] Impossible de récupérer le flux et de l\'enregistrer en cache.');
			} else {
				$this->_bRefreshedCache = true;
				log::add('gCalendar','info','['.$this->eqLogic_id.'|'.$this->id.'] Rafraichissement du fichier cache : OK.');
			}
		} else {
			log::add('gCalendar', 'error','['.$this->eqLogic_id.'|'.$this->id.'] URL gCalendar vide');
		}	
	}
	
	/**
     * Construit l'url gCalendar, récupère le XML et le sauvegarde dans un fichier
     * @param array $aOptions tableau des options de filtrage
     * @param string $_sGCalUrl url de l'agenda privé Google Agenda
	 * @return bool
	 */
	public function loadSaveXML(array $aOptions = array(), $_sGCalUrl = '') {
        // récupération des options //
        $_dStartMin = isset($aOptions['startmin']) ? $aOptions['startmin'] : date('Y-m-d');
        $_dStartMax = isset($aOptions['startmax']) ? $aOptions['startmax'] : '';
        $_sSortorder = isset($aOptions['sortorder']) ? $aOptions['sortorder'] : 'ascending';
        $_sOrderby = isset($aOptions['orderby']) ? $aOptions['orderby'] : 'starttime';
        $_iMaxResults = isset($aOptions['maxresults']) ? $aOptions['maxresults'] : 20; //GoogleAgenda::MAX_RESULTS_DEFAULT
        $_iStartIndex = isset($aOptions['startindex']) ? $aOptions['startindex'] : 1;
        $_sSearch = isset($aOptions['search']) ? $aOptions['search'] : '';
        $_bSingleEvents = isset($aOptions['singleevents']) ? $aOptions['singleevents'] : 'true';
        $_bFutureEvents = isset($aOptions['futureevents']) ? $aOptions['futureevents'] : 'false';
        $_sTimezone = isset($aOptions['timezone']) ? $aOptions['timezone'] : 'Europe/Paris';
        $_bShowDeleted = isset($aOptions['showdeleted']) ? $aOptions['showdeleted'] : 'false';

        // construction de l'url avec les options reçus //
        $_sGCalUrl = $_sGCalUrl . '?' .
        (!empty($_dStartMin) ? 'start-min=' . $_dStartMin . '&' : '' ) .
        (!empty($_dStartMax) ? 'start-max=' . $_dStartMax . '&' : '' ) .
        (!empty($_sSortorder) ? 'sortorder=' . $_sSortorder . '&' : '' ) .
        (!empty($_sOrderby) ? 'orderby=' . $_sOrderby . '&' : '' ) .
        (!empty($_iMaxResults) ? 'max-results=' . $_iMaxResults . '&' : '' ) .
        (!empty($_iStartIndex) ? 'start-index=' . $_iStartIndex . '&' : '' ) .
        (!empty($_sSearch) ? 'q=' . $_sSearch . '&' : '' ) .
        (!empty($_bSingleEvents) ? 'singleevents=' . $_bSingleEvents . '&' : '' ) .
        (!empty($_bFutureEvents) ? 'futureevents=' . $_bFutureEvents . '&' : '' ) .
        (!empty($_sTimezone) ? 'ctz=' . $_sTimezone . '&' : '' ) .
        (!empty($_bShowDeleted) ? 'showdeleted=' . $_bShowDeleted . '&' : '' );
		
		// définie les paramètres de timeout //
		$_sCtxt = stream_context_create(array('http'=>array('timeout'=>15)));
		// recupère le contenu du flux pour le mettre dans un fichier //
		$_sXmlGC = '';
		$_fGC = fopen($_sGCalUrl, "r", false, $_sCtxt);
		/*while (!feof($_fGC)) { $_sXmlGC .= fread($_fGC, 8192); }
		fclose($_fGC);*/
		if ($_fGC===false) {
			log::add('gCalendar','info','['.$this->eqLogic_id.'|'.$this->id.'] Le flux ('.$_sGCalUrl.') n\'est pas accéssible (error).');
			return false;
		} else { 
			while (!feof($_fGC)) { $_sXmlGC .= fread($_fGC, 8192); }
			$_aInfo = stream_get_meta_data($_fGC); 
			fclose($_fGC); 
			//log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] Retour du flux ='.print_r($_aInfo,true));
			if ($_aInfo['timed_out']>0) {
				log::add('gCalendar','info','['.$this->eqLogic_id.'|'.$this->id.'] Le flux ('.$_sGCalUrl.') n\'est pas accéssible (timeout).');
				return false;
			}
		} 
		// vérifie le contenu récupéré (et qu'il ressemble bien à un flux xml gCalendar) //
		if (!empty($_sXmlGC))
		{
			$oXml = simplexml_load_string($_sXmlGC);
			if ($oXml !== false) {
				// les champs "title", "update" et "author" doivent toujours être présents //
				if (isset($oXml->updated) && isset($oXml->title) && isset($oXml->author)) {
					$this->_sGCalUpdatedDate = date('Y-m-d H:i:s', strtotime($oXml->updated));
					$fo = fopen(GCALENDAR_CACHE_PATH.$this->getFileCacheName(), "w");
					if (!fwrite($fo, $_sXmlGC))  {
						log::add('gCalendar','error','['.$this->eqLogic_id.'|'.$this->id.'] Ecriture impossible dans le fichier : '.GCALENDAR_CACHE_PATH.$this->getFileCacheName().'.');
						return false; 
					}
					fclose($fo);
					log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] Sauvegarde du flux en cache complète.');
					return true; 
				} else {
					log::add('gCalendar','info','['.$this->eqLogic_id.'|'.$this->id.'] Le flux récupéré ne correspond pas à un flux XML GCalendar.');
				}
			} 
		}
		return false; 
	}
	
/*     * **********************Getteur Setteur*************************** */

	/**
     * Permet de récupérer le nom du fichier contenant le cache du flux
	 * @return string
	 */
	public function getFileCacheName() {
		return 'gCalendar_'.$this->eqLogic_id.'_'.$this->id.'.tmp.xml';
	}

	/**
     * Supprime le fichier en cache 
	 * @return bool
	 */
	public function deleteFileCache() {
		$fileCache = GCALENDAR_CACHE_PATH.$this->getFileCacheName();
		if (file_exists($fileCache)) {
			log::add('gCalendar','debug','['.$this->eqLogic_id.'|'.$this->id.'] preSave() suppression du fichier ('.$fileCache.')');
			unlink($fileCache);
			return true; 
		}
		return false;
	}

}

/**
 * Classe d'entité d'évènement Google Agenda
 * @author Shivato Web
 * @version 1.0
 *
 */
class GoogleAgendaEvent {
    /*     * *************************Attributs****************************** */

    protected $_sTitle;
    protected $_dStartDate;
    protected $_dEndDate;
    protected $_sAddress;
    protected $_sDescription;
    protected $_sAuthorName;
    protected $_sAuthorEmail;
    protected $_dPublishedDate;
    protected $_dUpdatedDate;
    protected $_sUrlDetail;
    protected $_aPersons = array();
    protected $_aReminders = array();
    protected $_dOriginalDate;
    protected $_bRecurs = false;

    /*     * ***********************Methode static*************************** */

    /**
     * Constructeur
     * @return void
     */
    public function __construct() {
        
    }

    /**
     * setteur titre
     * @param string $sTitle
     * @return void
     */
    public function setTitle($sTitle) {
        $this->_sTitle = $sTitle;
    }

    /**
     * getteur titre
     * @return string
     */
    public function getTitle() {
        return $this->_sTitle;
    }

    /**
     * setteur date de début
     * @param date $dStartDate
     * @return void
     */
    public function setStartDate($dStartDate) {
        $this->_dStartDate = $dStartDate;
    }

    /**
     * getteur date de début
     * @return date
     */
    public function getStartDate() {
        return $this->_dStartDate;
    }

    /**
     * setteur date de fin
     * @param date $dEndDate
     * @return void
     */
    public function setEndDate($dEndDate) {
        $this->_dEndDate = $dEndDate;
    }

    /**
     * getteur date de fin
     * @return date
     */
    public function getEndDate() {
        return $this->_dEndDate;
    }

    /**
     * setteur adresse
     * @param string $sAddress
     * @return void
     */
    public function setAddress($sAddress) {
        $this->_sAddress = $sAddress;
    }

    /**
     * getteur adresse
     * @return string
     */
    public function getAddress() {
        return $this->_sAddress;
    }

    /**
     * setteur description
     * @param string $sDescription
     * @return void
     */
    public function setDescription($sDescription) {
        $this->_sDescription = $sDescription;
    }

    /**
     * getteur description
     * @return string
     */
    public function getDescription() {
        return $this->_sDescription;
    }

    /**
     * setteur date de publication
     * @param date $dPublishedDate
     * @return void
     */
    public function setPublishedDate($dPublishedDate) {
        $this->_dPublishedDate = $dPublishedDate;
    }

    /**
     * getteur date de publication
     * @return date
     */
    public function getPublishedDate() {
        return $this->_dPublishedDate;
    }

    /**
     * setteur date de modification
     * @param date $dModifiedDate
     * @return void
     */
    public function setUpdatedDate($dUpdatedDate) {
        $this->_dUpdatedDate = $dUpdatedDate;
    }

    /**
     * getteur date de modification
     * @return date
     */
    public function getUpdatedDate() {
        return $this->_dUpdatedDate;
    }

    /**
     * setteur url détail
     * @param string $sUrlDetail
     * @return void
     */
    public function setUrlDetail($sUrlDetail) {
        $this->_sUrlDetail = $sUrlDetail;
    }

    /**
     * getteur url détail
     * @return string
     */
    public function getUrlDetail() {
        return $this->_sUrlDetail;
    }

    /**
     * setteur du nom de l'auteur de l'évènement
     * @param string $sAuthorName
     * @return void
     */
    public function setAuthorName($sAuthorName) {
        $this->_sAuthorName = $sAuthorName;
    }

    /**
     * getteur du nom de l'auteur de l'évènement
     * @return string
     */
    public function getAuthorName() {
        return $this->_sAuthorName;
    }

    /**
     * setteur du mail de l'auteur de l'évènement
     * @param string $sAuthorEmail
     * @return void
     */
    public function setAuthorEmail($sAuthorEmail) {
        $this->_sAuthorEmail = $sAuthorEmail;
    }

    /**
     * getteur du mail de l'auteur de l'évènement
     * @return string
     */
    public function getAuthorEmail() {
        return $this->_sAuthorEmail;
    }

    /**
     * setteur des personnes attaché à l'évènement
     * @param array $aPersons
     * @return void
     */
    public function setPersons(array $aPersons) {
        $this->_aPersons = $aPersons;
    }

    /**
     * getteur des personnes attaché à l'évènement
     * retourne un tableau d'objet de type stdClass() : $aPersons[0]->name, $aPersons[0]->email, $aPersons[0]->role, $aPersons[0]->status
     * @return array
     */
    public function getPersons() {
        return $this->_aPersons;
    }

    /**
     * setteur des rappels attaché à l'évènement
     * @param array $aReminders
     * @return void
     */
    public function setReminders(array $aReminders) {
        $this->_aReminders = $aReminders;
    }

    /**
     * getteur des rappels attaché à l'évènement
     * retourne un tableau d'objet de type stdClass() : $aReminders[0]->type, $aReminders[0]->minutes
     * @return array
     */
    public function getReminders() {
        return $this->_aReminders;
    }

    /**
     * setteur date d'origine
     * @param date $dDate
     * @return void
     */
    public function setOriginalDate($dOriginalDate) {
        $this->_dOriginalDate = $dOriginalDate;
    }

    /**
     * getteur date d'origine
     * @return date
     */
    public function getOriginalDate() {
        return $this->_dOriginalDate;
    }

    /**
     * setteur évènement récurrent
     * @param bool $bRecurs
     * @return void
     */
    public function setRecurs($bRecurs) {
        $this->_bRecurs = $bRecurs;
    }

    /**
     * getteur évènement récurrent
     * @return bool
     */
    public function getRecurs() {
        return $this->_bRecurs;
    }

}

/**
 * Classe de lecture d'un agenda Google
 * @author Shivato Web
 * @version 1.0
 * @link http://www.shivato-web.com/blog/php/tuto-classe-de-parsing-google-agenda-en-php
 * @example :
 * $oAgenda = new GoogleAgenda($sFeed);
 * $aEvents = $oAgenda->getEvents($aOptions);
 * $oAgenda->getTitle();
 * foreach ($aEvents as $oEvent) {
 *      $oEvent->getTitle();
 *      $oEvent->getStartDate();
 *      $oEvent->getEndDate();
 *      $oEvent->getAddress();
 *      $oEvent->getDescription();
 * }
 * $aEventsNext = $oAgenda->getNextEvents();
 * $aEventsPrevious = $oAgenda->getPreviousEvents(); $aEventsPrevious == $aEvents
 *
 * Les urls sont accessibles si on est logué sur le bon compte de l'agenda ou si l'agenda a été rendu public
 */
class GoogleAgenda {

    //variables interne de la classe
    protected $_sFeed;
    protected $_dStartMin;
    protected $_dStartMax;
    protected $_sSortorder;
    protected $_sOrderby;
    protected $_iMaxResults;
    protected $_iStartIndex;
    protected $_sUrlNext;
    protected $_sUrlPrevious;
    protected $_aEvents;
    protected $_sSearch;
    protected $_bSingleEvents;
    protected $_bFutureEvents;
    protected $_sTimezone;
    protected $_bShowDeleted;
    //variables disponible
    protected $_dUpdatedDate;
    protected $_sTitle;
    protected $_sSubtitle;
    protected $_sUrlPublic;
    protected $_sAuthorName;
    protected $_sAuthorEmail;

    const MAX_RESULTS_DEFAULT = 20;

    /**
     * Définie l'agenda avec lequel on travail
     * @param string $sFeed url de l'agenda
     * @param bool $bFull permet d'avoir toutes les variables rempli séparément, sinon met adresse, date... dans description (default false)
	 * @return void
     * @throws GoogleAgendaException si l'url n'est pas valide
     */
    public function __construct($sFeed, $bFull=true) {
		if ($bFull) {
			$sFeed = mb_strrchr($sFeed, 'basic', true) . 'full';
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $sFeed);
		curl_setopt($ch, CURLOPT_REFERER, $sFeed);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$sFeedContent = curl_exec($ch);
		curl_close($ch);
		//$sFeedContent = @file_get_contents($sFeed);
		if ($sFeedContent !== false && !empty($sFeedContent)) {
			$this->_sFeed = $sFeed;
		} else {
			throw new GoogleAgendaException(__('L\'url [', __FILE__) . $sFeed . __('] n\'est pas valide.', __FILE__));
		}
	}

    /**
     * getteur de la date de maj de l'agenda
     * @return date
     */
    public function getUpdatedDate() {
        return $this->_dUpdatedDate;
    }

    /**
     * getteur du titre de l'agenda
     * @return string
     */
    public function getTitle() {
        return $this->_sTitle;
    }

    /**
     * getteur du sous titre de l'agenda
     * @return string
     */
    public function getSubtitle() {
        return $this->_sSubtitle;
    }

    /**
     * getteur de l'url public de l'agenda
     * @return string
     */
    public function getUrlPublic() {
        return $this->_sUrlPublic;
    }

    /**
     * getteur du nom de l'auteur de l'agenda
     * @return string
     */
    public function getAuthorName() {
        return $this->_sAuthorName;
    }

    /**
     * getteur de l'adresse email de l'auteur de l'agenda
     * @return string
     */
    public function getAuthorEmail() {
        return $this->_sAuthorEmail;
    }

    /**
     * Getteur des évènements selon les paramètres
     * Options :
     * (date Y-m-d) startmin : date du début de la lecture (default : date du jour)
     * (date Y-m-d) startmax : date de la fin de la lecture (ne prend pas les évènement de la date) (default : null)
     * (string) sortorder : tri des évènements, options disponible : ascending, descending (default : ascending)
     * (string) orderby : ordre des évènements, options disponible : starttime, lastmodified (default : starttime)
     * (int) maxresults : nombre d'évènements retournés (default : self::MAX_RESULTS_DEFAULT)
     * (int) startindex : page de résultat de la lecture (default : 1)
     * (string) search : texte recherché dans les évènements (default : null)
     * (string) singleevents : prend les évènements récurrents à leur date, sinon toutes les dates suivantes sont dans le premier évènement récurrent trouvé
     *               (déconseillé, ne marche pas vraiment bien), options : 'true', 'false' (default : 'true')
     * (string) futureevents : ne prend que les évènements à venir ou prend aussi ceux déjà passé de la première journée, options : 'true', 'false' (default : 'false')
     * (string) timezone : défini le fuseau horaire (default : Europe/Paris)
     * (string) showdeleted : prend en compte les évènements supprimés, options : 'true', 'false' (default : 'false')
     * @param array $aOptions (options : startmin, startmax, sortorder, orderby, maxresults, startindex, search, singleevents, futureevents, timezone, showdeleted)
     * @return array tableau d'objets des évènements de l'agenda
     * @link options : http://code.google.com/intl/fr/apis/calendar/data/2.0/reference.html#Parameters
     * @link options : http://code.google.com/intl/fr/apis/gdata/docs/2.0/reference.html#Queries
     */
    public function getEvents(array $aOptions = array()) {
		//récupération des options
		$this->_dStartMin = isset($aOptions['startmin']) ? $aOptions['startmin'] : date('Y-m-d');
		$this->_dStartMax = isset($aOptions['startmax']) ? $aOptions['startmax'] : '';
		$this->_sSortorder = isset($aOptions['sortorder']) ? $aOptions['sortorder'] : 'ascending';
		$this->_sOrderby = isset($aOptions['orderby']) ? $aOptions['orderby'] : 'starttime';
		$this->_iMaxResults = isset($aOptions['maxresults']) ? $aOptions['maxresults'] : self::MAX_RESULTS_DEFAULT;
		$this->_iStartIndex = isset($aOptions['startindex']) ? $aOptions['startindex'] : 1;
		$this->_sSearch = isset($aOptions['search']) ? $aOptions['search'] : '';
		$this->_bSingleEvents = isset($aOptions['singleevents']) ? $aOptions['singleevents'] : 'true';
		$this->_bFutureEvents = isset($aOptions['futureevents']) ? $aOptions['futureevents'] : 'false';
		$this->_sTimezone = isset($aOptions['timezone']) ? $aOptions['timezone'] : 'Europe/Paris';
		$this->_bShowDeleted = isset($aOptions['showdeleted']) ? $aOptions['showdeleted'] : 'false';

		//construction de l'url avec les options reçus
		$sUrl = $this->_sFeed . '?' .
		(!empty($this->_dStartMin) ? 'start-min=' . $this->_dStartMin . '&' : '' ) .
		(!empty($this->_dStartMax) ? 'start-max=' . $this->_dStartMax . '&' : '' ) .
		(!empty($this->_sSortorder) ? 'sortorder=' . $this->_sSortorder . '&' : '' ) .
		(!empty($this->_sOrderby) ? 'orderby=' . $this->_sOrderby . '&' : '' ) .
		(!empty($this->_iMaxResults) ? 'max-results=' . $this->_iMaxResults . '&' : '' ) .
		(!empty($this->_iStartIndex) ? 'start-index=' . $this->_iStartIndex . '&' : '' ) .
		(!empty($this->_sSearch) ? 'q=' . $this->_sSearch . '&' : '' ) .
		(!empty($this->_bSingleEvents) ? 'singleevents=' . $this->_bSingleEvents . '&' : '' ) .
		(!empty($this->_bFutureEvents) ? 'futureevents=' . $this->_bFutureEvents . '&' : '' ) .
		(!empty($this->_sTimezone) ? 'ctz=' . $this->_sTimezone . '&' : '' ) .
		(!empty($this->_bShowDeleted) ? 'showdeleted=' . $this->_bShowDeleted . '&' : '' );

		$this->loadUrl($sUrl);
        return $this->_aEvents;
    }

    /**
     * Getteur des évènements suivants avec les mêmes paramètres
     * @return array tableau d'objets des évènements de l'agenda, un tableau vide si l'url n'existe pas
     */
    public function getNextEvents() {
        if (!empty($this->_sUrlNext)) {
            $this->loadUrl($this->_sUrlNext);
            return $this->_aEvents;
        } else {
            return array();
        }
    }

    /**
     * Getteur des évènements précédents avec les mêmes paramètres
     * Utilisable si la fonction getNextEvents() a été utilisés ou si l'option start-index > 1 a été utilisé
     * @return array tableau d'objets des évènements de l'agenda, un tableau vide si l'url n'existe pas
     */
    public function getPreviousEvents() {
        if (!empty($this->_sUrlPrevious)) {
            $this->loadUrl($this->_sUrlPrevious);
            return $this->_aEvents;
        } else {
            return array();
        }
    }

    /**
     * Charge l'url du flux xml de l'agenda et rempli les valeurs de l'instance correspondant à l'agenda
     * @param string $sUrl
     * @return void
     */
    protected function loadUrl($sUrl) {
        $this->_aEvents = array();
		//log::add('gCalendar','debug','loadUrl().sUrl:'.$sUrl);

        //lecture du fichier XML
        $oXml = simplexml_load_file($sUrl);
		//log::add('gCalendar','debug','loadUrl().oXml:'.print_r($oXml,true));
        if ($oXml !== false) {
            $this->_dUpdatedDate = isset($oXml->updated) ? date('Y-m-d H:i:s', strtotime($oXml->updated)) : '';
            $this->_sTitle = isset($oXml->title) ? (string) $oXml->title : '';
            $this->_sSubtitle = isset($oXml->subtitle) ? (string) $oXml->subtitle : '';
            $this->_sAuthorName = isset($oXml->author) && isset($oXml->author->name) ? (string) $oXml->author->name : '';
            $this->_sAuthorEmail = isset($oXml->author) && isset($oXml->author->email) ? (string) $oXml->author->email : '';
            $this->_sUrlPublic = '';
            $this->_sUrlNext = '';
            $this->_sUrlPrevious = '';
            if (isset($oXml->link)) {
                foreach ($oXml->link as $oLink) {
                    if ($oLink->attributes()->rel == 'alternate') {
                        $this->_sUrlPublic = (string) $oLink->attributes()->href;
                    } else if ($oLink->attributes()->rel == 'next') {
                        $this->_sUrlNext = (string) $oLink->attributes()->href;
                    } else if ($oLink->attributes()->rel == 'previous') {
                        $this->_sUrlPrevious = (string) $oLink->attributes()->href;
                    }
                }
            }
            if (isset($oXml->entry)) {
				log::add('gCalendar','debug','loadUrl().nb entry:'.count($oXml->entry));
                foreach ($oXml->entry as $oDataEvent) {
                    $this->setEvent($oDataEvent);
                }
            }
        }
    }

    /**
     * Crée un nouvel objet GoogleAgendaEvent et l'affecte au tableau d'évènements
     * @param SimpleXMLElement $oData
     * @return void
     */
    protected function setEvent(SimpleXMLElement $oData) {
	    $oEvent = new GoogleAgendaEvent();
        $oDataChild = $oData->children('http://schemas.google.com/g/2005');

        $oEvent->setTitle(isset($oData->title) ? (string) $oData->title : '');
        $oEvent->setPublishedDate(isset($oData->published) ? date('Y-m-d H:i:s', strtotime($oData->published)) : '');
        $oEvent->setUpdatedDate(isset($oData->updated) ? date('Y-m-d H:i:s', strtotime($oData->updated)) : '');
        $oEvent->setAuthorName(isset($oData->author) && isset($oData->author->name) ? (string) $oData->author->name : '');
        $oEvent->setAuthorEmail(isset($oData->author) && isset($oData->author->email) ? (string) $oData->author->email : '');
        $oEvent->setDescription(isset($oData->content) ? (string) $oData->content : '');
        $oEvent->setAddress(isset($oDataChild->where) ? (string) $oDataChild->where->attributes()->valueString : '');

        if (isset($oData->link)) {
            foreach ($oData->link as $oLink) {
                if ($oLink->attributes()->rel == 'alternate') {
                    $oEvent->setUrlDetail((string) $oLink->attributes()->href);
                    break;
                }
            }
        }

        if (isset($oDataChild->who)) {
            $aPersons = array();
            foreach ($oDataChild->who as $oWho) {
                $aPersons[] = $this->parsePerson($oWho);
            }
            $oEvent->setPersons($aPersons);
        }

        if (isset($oDataChild->originalEvent)) {
            $oEvent->setOriginalDate((string) $oDataChild->originalEvent->when->attributes()->startTime);
        }

        if (isset($oDataChild->when)) {
            $oEvent->setStartDate(date('Y-m-d H:i:s', strtotime($oDataChild->when->attributes()->startTime)));
            $oEvent->setEndDate(date('Y-m-d H:i:s', strtotime($oDataChild->when->attributes()->endTime)));

            if (isset($oDataChild->when->reminder)) {
                $aReminders = array();
                foreach ($oDataChild->when->reminder as $oReminder) {
                    $oReminderEvent = new stdClass();
                    $oReminderEvent->type = (string) $oReminder->attributes()->method;
                    $oReminderEvent->minutes = (string) $oReminder->attributes()->minutes;
                    $aReminders[] = $oReminderEvent;
                }
                $oEvent->setReminders($aReminders);
            }
        }

        if (isset($oDataChild->recurrence)) {
            $oEvent->setRecurs(true);
        }

        $this->_aEvents[] = $oEvent;
    }
	
    /**
     * Parse les informations des personnes participantes
     * @param SimpleXMLElement $oPerson
     * @return stdClass
     */
    protected function parsePerson(SimpleXMLElement $oPerson) {
        if ($oPerson->attributes()->rel == 'http://schemas.google.com/g/2005#event.organizer') {
            $sRole = 'Organisateur';
        } else {
            $sRole = 'Invité';
        }

        if (isset($oPerson->attendeeStatus)) {
            switch ($oPerson->attendeeStatus->attributes()->value) {
                case 'http://schemas.google.com/g/2005#event.accepted' :
                $sStatus = __('Présent', __FILE__);
                break;
                case 'http://schemas.google.com/g/2005#event.invited' :
                $sStatus = __('Invité', __FILE__);
                break;
                case 'http://schemas.google.com/g/2005#event.declined' :
                $sStatus = __('Absent', __FILE__);
                break;
                case 'http://schemas.google.com/g/2005#event.tentative' :
                $sStatus = __('Peut-être', __FILE__);
                break;
                default :
                $sStatus = __('Présent', __FILE__);
            }
        } else {
            $sStatus = __('Présent', __FILE__);
        }

        $oPersonEvent = new stdClass();
        $oPersonEvent->name = (string) $oPerson->attributes()->valueString;
        $oPersonEvent->email = (string) $oPerson->attributes()->email;
        $oPersonEvent->role = $sRole;
        $oPersonEvent->status = $sStatus;
        return $oPersonEvent;
    }

}

class GoogleAgendaException extends Exception {
    
}


/**
 * Extend de la Classe Google Agenda, pour Jeedom
 * @author Aurelien Barrau
 * @version 1.0
 */
class JeeGoogleAgenda extends GoogleAgenda {
	protected $_bLocalFile;

    /**
     * Complément Jeedom de la Class initiale GoogleAgenda
     * @param string $sFeed (même utilisation)
     * @param bool $bFull (même utilisation), mais avec une valeur "false" par défaut, car ne semble plus fonctionner en v2 (depuis l'arrivée de l'api v3)
     * @param bool $bLocalFile (add jeedom) permet de traiter un fichier en local et non une url (default true)
	 * @return void
     * @throws GoogleAgendaException si l'url n'est pas valide
     */
    public function __construct($sFeed, $bFull=false, $bLocalFile=true) {
		// gestion du $bLocalFile //
		if ($bLocalFile) {
			$this->_bLocalFile = $bLocalFile;
			if (file_exists($sFeed)) {
				$this->_sFeed = $sFeed;
			} else {
				throw new GoogleAgendaException(__('Le fichier [', __FILE__) . $sFeed . __('] n\'existe pas.', __FILE__));
			}
		} else {
			parent::__construct($sFeed, $bFull);
		}
	}
	
    /**
     * {Complément Jeedom} Getteur des évènements selon les paramètres
     * Options : mêmes options que la class initiale ; >> dans le cas de jeedom, les options ne sont pas utilisées dans cette class.
	 */
    public function getEvents(array $aOptions = array()) {
		// gestion fichier local, pas besoin de filtre //
		if ($this->_bLocalFile) {
			$this->loadUrl($this->_sFeed);
			return $this->_aEvents;
		} else {
			return parent::getEvents($aOptions);
		}
    }

    /**
     * {Complément Jeedom} Crée un nouvel objet GoogleAgendaEvent et l'affecte au tableau d'évènements
     * Même param et return que la class initiale
	 *		- permet de gérer les StartDate et EndDate, par rapport à ce qui a été retourné par la class initiale
     */
    protected function setEvent(SimpleXMLElement $oData) {
		// traitement initial //
		parent::setEvent($oData);
		$nNbEvent = count($this->_aEvents);
		
		// actions compémentaires uniquement s'il y a des données dans le tableau //
		if ($nNbEvent>0) {
			$oDataChild = $oData->children('http://schemas.google.com/g/2005');
			$oEvent = $this->_aEvents[$nNbEvent-1];
			$sEventStartDate = (string) $oEvent->getStartDate();
			$sEventEndDate = (string) $oEvent->getEndDate();
			
			// verifie si le traitement est valide //
			if ((!isset($oDataChild->when)) || (empty($sEventStartDate) && empty($sEventEndDate))) {
				// permet de gérer StartDate et EndDate à partir de la Description (tag <content>), si tag <when> absent du flux //
				$eventDesciption = $this->changeString($oEvent->getDescription());
				log::add('gCalendar','debug','['.$oEvent->getTitle().'] setEvent().description:/Start/'.$eventDesciption.'/End/');
				if (!empty($eventDesciption)) {
					// (1 journée entière, sans horaires) | Date : Lun. 26 Janv. 2015<br /> ... //
					if (preg_match('@Date : (\w+)[.]{0,1} (\d{1,2}) (\w+)[.]{0,1} (\d{4})<br@i',$eventDesciption, $resMatsh)==1) {
						$oEvent->setStartDate(date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' 00:00:00'));
						$oEvent->setEndDate(date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' 23:59:59'));
						log::add('gCalendar','debug','['.$oEvent->getTitle().'] setEvent() (1j full) start:'.date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' 00:00:00').' - end:'.date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' 23:59:59'));
					}
					
					// (horaire dans le jour courant) | Date : Lun. 26 Janv. 2015 07:30 au 18:00  ... //
					elseif (preg_match('@Date : (\w+)[.]{0,1} (\d{1,2}) (\w+)[.]{0,1} (\d{4}) (\d{2}:\d{2}) \w+ (\d{2}:\d{2})@i',$eventDesciption, $resMatsh)==1) {
						$resMatsh[6] = ($resMatsh[6]=='00:00')?'23:59:59':$resMatsh[6].':00';
						$oEvent->setStartDate(date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' '.$resMatsh[5].':00'));
						$oEvent->setEndDate(date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' '.$resMatsh[6]));
						log::add('gCalendar','debug','['.$oEvent->getTitle().'] setEvent() (1j heure) start:'.date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' '.$resMatsh[5].':00').' - end:'.date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' '.$resMatsh[6]));
					} 
										
					// (évènement sur plusieurs journées avec heure) | Date : Dim. 25 Janv. 2015 23:00 au Lun. 26 Janv. 2015 21:00 ... //
					elseif (preg_match('@Date : (\w+)[.]{0,1} (\d{1,2}) (\w+)[.]{0,1} (\d{4}) (\d{2}:\d{2}) \w+ (\w+)[.]{0,1} (\d{1,2}) (\w+)[.]{0,1} (\d{4}) (\d{2}:\d{2})@i',$eventDesciption, $resMatsh)==1) {
						$resMatsh[10] = ($resMatsh[10]=='00:00')?'23:59:59':$resMatsh[10].':00';
						$oEvent->setStartDate(date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' '.$resMatsh[5].':00'));
						$oEvent->setEndDate(date($resMatsh[9].'-'.$this->getMonthNumber($resMatsh[8]).'-'.$resMatsh[7].' '.$resMatsh[10]));
						log::add('gCalendar','debug','['.$oEvent->getTitle().'] setEvent() (xj heure) start:'.date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' '.$resMatsh[5].':00').' - end:'.date($resMatsh[9].'-'.$this->getMonthNumber($resMatsh[8]).'-'.$resMatsh[7].' '.$resMatsh[10]));
					}
					
					// (évènement sur plusieurs journées sans heure) | Date : Dim. 25 Janv. 2015 au Lun. 26 Janv. 2015 ... //
					elseif (preg_match('@Date : (\w+)[.]{0,1} (\d{1,2}) (\w+)[.]{0,1} (\d{4}) \w+ (\w+)[.]{0,1} (\d{1,2}) (\w+)[.]{0,1} (\d{4})@i',$eventDesciption, $resMatsh)==1) {
						$oEvent->setStartDate(date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' 00:00:00'));
						$oEvent->setEndDate(date($resMatsh[8].'-'.$this->getMonthNumber($resMatsh[7]).'-'.$resMatsh[6].' 23:59:59'));
						log::add('gCalendar','debug','['.$oEvent->getTitle().'] setEvent() (xj) start:'.date($resMatsh[4].'-'.$this->getMonthNumber($resMatsh[3]).'-'.$resMatsh[2].' 00:00:00').' - end:'.date($resMatsh[8].'-'.$this->getMonthNumber($resMatsh[7]).'-'.$resMatsh[6].' 23:59:59'));
					} else {
						log::add('gCalendar','info','['.$oEvent->getTitle().'] setEvent() Le champ description(content) n\'est pas correctement formaté (impossible de déterminer les dates de l\'évènement).');
					}
					//log::add('gCalendar','debug','['.$oEvent->getTitle().'] setEvent().preg_match='.print_r($resMatsh,true));	
				}
			}
			$this->_aEvents[$nNbEvent-1] = $oEvent;
		}
	}
	
	/**
	 *	Retourne le numéro du mois
	 *	@param string $m mois abrégé en français, reçu de Google
	 */
	public function getMonthNumber($m=0) {
		//$month = array('Janv','Févr','Mars','Avr','Mai','Juin','Juil','Août','Sept','Oct','Nov','Déc');
		$month = array('Janv','Fevr','Mars','Avr','Mai','Juin','Juil','Aout','Sept','Oct','Nov','Dec');
		$m=(array_search($m, $month)+1);
		if (($m>=1)&&($m<=12)) return $m;
			else return 0;
	}

	/**
	 *	Met en forme la donnée pour permettre son traitement
	 *	@param string $s contenu à mettre en forme
	 */
	public function changeString($s=0) {
		$s=nl2br($s);
		$s=str_replace('é','e',$s);
		$s=str_replace('û','u',$s);
		return $s;		
	}	
}

?>
