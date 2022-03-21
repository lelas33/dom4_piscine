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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

// Constantes pour les modes de filtration
define("FILT_ARRET",  0);
define("FILT_MANUEL", 1);
define("FILT_AUTO",   2);
define("FILT_HIVER",  3);

// Constantes pour les modes de pompe a chaleur
define("PAC_ARRET",  0);
define("PAC_TFILT",  1);
define("PAC_TETEN",  2);
define("PAC_MANUEL", 3);

// Constantes de fonctionnement de la filtration
define("FILT_POMPE_START_TIME",   8*60+ 0);    // Demarrage pompe filtration a 8h00 
define("FILT_POMPE_DUREE_MANUEL", 7*60+ 0);    // Duree filtration manuelle de 7h00 
define("FILT_DUREE_SEL",          2*60+15);    // Duree traitement sel 2h15 (1 cycle)
define("SEL_DUREE_PAUSE",              20);    // Temps entre 2 cycles de sel : 20mn
define("SEL_NBCYCLES",                  2);    // 2 cycles de sel par jour
define("FILT_HIV_START1",         6*60+ 0);    // Mode hiver: heure demarrage cycle matin
define("FILT_HIV_START2",        19*60+ 0);    // Mode hiver: heure demarrage cycle soir
define("FILT_HIV_DUREE1",         1*60+ 0);    // Mode hiver: duree cycle matin
define("FILT_HIV_DUREE2",         1*60+ 0);    // Mode hiver: duree cycle soir
define("PAC_TEMPE_HYSTERESIS",        0.5);    // Hysteresis temperature PAC

// Constantes de fonctionnement de la Pompe a chaleur
define("PAC_MAX_DURATION_DAY",        840);    // Duree maximale de fonctionnement de la PAC sur la journee:14h (en mn)


class dom4_piscine extends eqLogic {
    /*     * *************************Attributs****************************** */
    
    // Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
    // Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	  // public static $_widgetPossibility = array();
    
    /* ************************Methode static*************************** */
    // Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {
      foreach (self::byType('dom4_piscine') as $eqLogic) {
        $eqLogic->periodic_task($eqLogic);
      }
    }

    // Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
    // public static function cron5() { }

    // Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
    // public static function cron10() { }

    // Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
    // public static function cron15() { }
    
    // Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
    // public static function cron30() { }

    // Fonction exécutée automatiquement toutes les heures par Jeedom
    // public static function cronHourly() { }

    // Fonction exécutée automatiquement tous les jours par Jeedom
    // public static function cronDaily() { }

    private function getListeDefaultCommandes()
    {
      return array( // Commandes: modes filtration
        "modef_arret"     => array('Flt:Arrêt',               'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "modef_manuel"    => array('Flt:Manuel',              'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "modef_auto"      => array('Flt:Automatique',         'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "modef_hiver"     => array('Flt:Hiver',               'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        // Commandes: modes PAC                                                     
        "modep_arret"     => array('PAC:Arrêt',               'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "modep_tfilt"     => array('PAC:Temps filtration',    'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "modep_teten"     => array('PAC:Temps étendu',        'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "modep_manuel"    => array('PAC:Manuel',              'action', 'other',    "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        "pac_tconsigne"   => array('Température consigne',    'action', 'slider',   "", 0, 1, "GENERIC_ACTION", 'default', 'default'),
        // Infos: Etats & parametres                                                
        "modef_value"     => array('Mode filtration',         'info',  'numeric',   "", 0, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "modep_value"     => array('Mode pompe à chaleur',    'info',  'numeric',   "", 0, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "filtr_state"     => array('Etat courant filtration', 'info',  'numeric',   "", 1, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "st_filt"         => array('Etat pompe filtration',   'info',  'binary',    "", 1, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "st_sel"          => array('Etat traitement sel',     'info',  'binary',    "", 1, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "st_pac"          => array('Etat pompe à chaleur',    'info',  'binary',    "", 1, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "st_dureef"       => array('Durée Filtration',        'info',  'numeric', "mn", 0, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "st_durees"       => array('Durée Sel',               'info',  'numeric', "mn", 0, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "tconsigne_val"   => array('Valeur T.consigne PAC',   'info',  'numeric', "°C", 0, 1, "GENERIC_INFO", 'core::badge', 'core::badge'),
        "ptemp_pisc"      => array('Temp. Piscine',           'info',  'numeric', "°C", 0, 1, "GENERIC_INFO", 'core::badge', 'core::badge')
      );
    }


    /* *********************Méthodes d'instance************************* */
    
 // Fonction exécutée automatiquement avant la création de l'équipement 
    public function preInsert() {
        
    }

 // Fonction exécutée automatiquement après la création de l'équipement 
    public function postInsert() {
        
    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement 
    public function preUpdate() {
        
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
 // ======================================================================
    public function postUpdate() {

      // creation de la liste des commandes / infos
      // ------------------------------------------
      foreach( $this->getListeDefaultCommandes() as $id => $data) {
        list($name, $type, $subtype, $unit, $hist, $visible, $generic_type, $template_dashboard, $template_mobile) = $data;
        $cmd = $this->getCmd(null, $id);
        if (! is_object($cmd)) {
          // New CMD
          $cmd = new dom4_piscineCmd();
          $cmd->setName($name);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setType($type);
          if ($type == "info") {
            $cmd->setDisplay ("showStatsOndashboard",0);
            $cmd->setDisplay ("showStatsOnmobile",0);
          }
          $cmd->setSubType($subtype);
          if ($id == "pac_tconsigne") {
            $cmd->setConfiguration('minValue', 20);
            $cmd->setConfiguration('maxValue', 35);
            // $cmd->setConfiguration('step', 0.5);
          }
          $cmd->setUnite($unit);
          $cmd->setLogicalId($id);
          $cmd->setIsHistorized($hist);
          $cmd->setIsVisible($visible);
          $cmd->setDisplay('generic_type', $generic_type);
          $cmd->setTemplate('dashboard', $template_dashboard);
          $cmd->setTemplate('mobile', $template_mobile);
          $cmd->save();
        }
        else {
          // Upadate CMD
          $cmd->setType($type);
          if ($type == "info") {
            $cmd->setDisplay ("showStatsOndashboard",0);
            $cmd->setDisplay ("showStatsOnmobile",0);
          }
          $cmd->setSubType($subtype);
          if ($id == "pac_tconsigne") {
            $cmd->setConfiguration('minValue', 20);
            $cmd->setConfiguration('maxValue', 35);
            // $cmd->setConfiguration('step', 0.5);
          }
          $cmd->setUnite($unit);
          // $cmd->setIsHistorized($hist);
          // $cmd->setIsVisible($visible);
          $cmd->setDisplay('generic_type', $generic_type);
          // $cmd->setTemplate('dashboard', $template_dashboard);
          // $cmd->setTemplate('mobile', $template_mobile);
        }
      }

      // couplage des commandes et infos : "pac_tconsigne" et "tconsigne_val"
      $cmd_act = $this->getCmd(null, 'pac_tconsigne');
      $cmd_inf = $this->getCmd(null, 'tconsigne_val');
      if ((is_object($cmd_act)) and (is_object($cmd_inf))) {
        $cmd_act->setValue($cmd_inf->getid());
        $cmd_act->save();
      }
      
      // ajout de la commande refresh data
      $refresh = $this->getCmd(null, 'refresh');
      if (!is_object($refresh)) {
        $refresh = new dom4_piscineCmd();
        $refresh->setName(__('Rafraichir', __FILE__));
      }
      $refresh->setEqLogic_id($this->getId());
      $refresh->setLogicalId('refresh');
      $refresh->setType('action');
      $refresh->setSubType('other');
      $refresh->save();
      log::add('dom4_piscine','debug','postSave:Ajout ou Mise des commandes et infos');

    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement 
    public function preSave() {
        
    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement 
    public function postSave() {
        
    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement 
    public function preRemove() {
        
    }

 // Fonction exécutée automatiquement après la suppression de l'équipement 
    public function postRemove() {
        
    }

    // Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
    //  public function toHtml($_version = 'dashboard') { }

    // Non obligatoire : permet de déclencher une action après modification de variable de configuration
    // public static function postConfig_<Variable>() { }

    // Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    // public static function preConfig_<Variable>() { }

    /* ********************** Getter Setter *************************** */



    // Fonction exécutée périodiquement (à la minute)
    // ==============================================
    public static function periodic_task($eql) {
      // log::add('dom4_piscine','debug','periodic_task: Start');
      $minute = intval(date("i"));
      $heure  = intval(date("G"));
      $cur_hm = intval($heure*60)+intval($minute);

      // Capture de la température, et recopie dans le champ info:ptemp_pisc
      $tempe_cmdname = str_replace('#', '', $eql->getConfiguration('EAU_temperature'));
      $tempe_cmd  = cmd::byId($tempe_cmdname);
      if (!is_object($tempe_cmd)) {
        throw new Exception(__('Impossible de trouver la commande Temperature piscine', __FILE__));
      }
      $tempe_eau = $tempe_cmd->execCmd();
      $cmd = $eql->getCmd(null, 'ptemp_pisc');
      if (is_object($cmd)) {
        $cmd->event($tempe_eau);
      }
      $tempe_abri = 10.0;  // TODO : capturer temperature abri
      // Capture des commandes de pilotage de la piscine (pompe filtration, sel, pac)
      $cmd_flt_on_name  = str_replace('#', '', $eql->getConfiguration('Filtration_on'));
      $cmd_flt_off_name = str_replace('#', '', $eql->getConfiguration('Filtration_off'));
      $cmd_sel_on_name  = str_replace('#', '', $eql->getConfiguration('SEL_on'));
      $cmd_sel_off_name = str_replace('#', '', $eql->getConfiguration('SEL_off'));
      $cmd_pac_on_name  = str_replace('#', '', $eql->getConfiguration('PAC_on'));
      $cmd_pac_off_name = str_replace('#', '', $eql->getConfiguration('PAC_off'));
      $cmd_flt_on  = cmd::byId($cmd_flt_on_name );
      $cmd_flt_off = cmd::byId($cmd_flt_off_name);
      $cmd_sel_on  = cmd::byId($cmd_sel_on_name );
      $cmd_sel_off = cmd::byId($cmd_sel_off_name);
      $cmd_pac_on  = cmd::byId($cmd_pac_on_name );
      $cmd_pac_off = cmd::byId($cmd_pac_off_name);
      if ((!is_object($cmd_flt_on)) || (!is_object($cmd_flt_off)) || (!is_object($cmd_sel_on)) || (!is_object($cmd_sel_off)) || (!is_object($cmd_pac_on)) || (!is_object($cmd_pac_off))) {
        throw new Exception(__('Impossible de trouver les commandes controle piscine', __FILE__));
      }

      // -------------------------------------------------
      // Gestion de la filtration piscine + traitement sel
      // -------------------------------------------------
      // Variable "statiques" de l'algo, sauvegardee dans la config de la commande "state_filtration"
      $cmd_filtr_state = $eql->getCmd(null, 'filtr_state');
      $filtration_state = $cmd_filtr_state->execCmd();
      if ($filtration_state == '')
        $filtration_state = 0;
      $param_filtr = $cmd_filtr_state->getConfiguration('param_filtration');
      if ($param_filtr == "") {
        $param_filtr["flag_ag_onoff"]   = 0;
        $param_filtr["flag_pac_tempe_rising"] = 1;
        $param_filtr["filt_start_time"] = 0;
        $param_filtr["filt_end_time"]   = 0;
        $param_filtr["sel_end_time"]    = 0;
        $param_filtr["cptcycle_sel"]    = 0;
        $param_filtr["psel_end_pause"]  = 0;
        $param_filtr["pac_start_time"]  = 0;
      }
      
      $cmd = $eql->getCmd(null, 'modef_value');
      $mode_filtration = $cmd->execCmd();
      $cmd = $eql->getCmd(null, 'modep_value');
      $mode_pac = $cmd->execCmd();
      $cmd = $eql->getCmd(null, 'tconsigne_val');
      $tempe_consigne_pac = $cmd->execCmd();

      // log::add('dom4_piscine','debug','Gestion filtration, en mode: '.$mode_filtration);

      // Gestion du mode "ETE"
      // ---------------------
      // Valeurs utilisees pour la variable d'etat du filtrage piscine : $filtration_state
      //  0 : Valeur apres une initialisation hardware
      //  Valeur pour mode "ETE"
      //  1 : Attente de demarrage du cycle de filtrage
      //  2 : Cycle filtrage en cours + Sel en cours
      //  3 : Cycle filtrage en cours + Sel en pose entre 2 cycles
      //  4 : Cycle filtrage en cours + Sel termine
      //  5 : Cycle filtrage termine + Sel termine ( attente cycle du jour suivant )
      // Valeur pour mode "HIVER"
      // 10 : Attente de demarrage du cycle de filtrage de la premiere tranche
      // 11 : Cycle filtrage en cours : Premiere tranche horaire
      // 12 : Attente de demarrage du cycle de filtrage de la seconde tranche
      // 13 : Cycle filtrage en cours : Seconde tranche horaire
      // 14 : Cycle filtrage termine ( attente cycle du jour suivant )
      $pmp_filt_am = 0;
      $sel_am      = 0;
      if ($mode_filtration == FILT_ARRET) {
        $pmp_filt_am = 0;
        $sel_am      = 0;
      }
      else if (($mode_filtration == FILT_MANUEL) || ($mode_filtration == FILT_AUTO)) {
        // Demarrage du cycle a 3h00 
        if (($filtration_state == 0) || (($heure == 3) && ($minute == 0))) {
          // Initialisation du cycle quotidien a 3h00
          // pause_filt = 0;  // fin de pause au cas ou elle serait active par oubli
          $param_filtr["filt_start_time"] = FILT_POMPE_START_TIME;
          $duree_filtration = $eql->pis_calcule_duree_filtration ($tempe_eau);
          if ($mode_filtration == FILT_MANUEL) {
            $param_filtr["filt_end_time"] = $param_filtr["filt_start_time"] + FILT_POMPE_DUREE_MANUEL;
            if (($mode_pac == PAC_TETEN) && (PAC_MAX_DURATION_DAY > FILT_POMPE_DUREE_MANUEL)) {
              $param_filtr["filt_end_time"] = $param_filtr["filt_start_time"] + PAC_MAX_DURATION_DAY;
            }
          }
          else if ($mode_filtration == FILT_AUTO) {
            $param_filtr["filt_end_time"] = $param_filtr["filt_start_time"] + $duree_filtration;
            if (($mode_pac == PAC_TETEN) && (PAC_MAX_DURATION_DAY > $duree_filtration)) {
              $param_filtr["filt_end_time"] = $param_filtr["filt_start_time"] + PAC_MAX_DURATION_DAY;
            }
          }
          $param_filtr["sel_end_time"] = $param_filtr["filt_start_time"] + FILT_DUREE_SEL;
          $pmp_filt_am = 0;
          $sel_am      = 0;
          $filtration_state = 1;
          }
        else if ($filtration_state == 1) {
          // Attente heure de depart du cycle filtration
          if ($cur_hm >= $param_filtr["filt_start_time"]) {
            $pmp_filt_am = 1;   //demarre pompe
            $sel_am      = 1;   //demarre sel
            $param_filtr["cptcycle_sel"] = 1;   // Premier cycle de sel
            $filtration_state = 2;
            $tmp = (($mode_filtration == FILT_MANUEL) ? FILT_POMPE_DUREE_MANUEL:$duree_filtration)/60.0;
            log::add('dom4_piscine','info','Demarrage Pompe pour duree:'.$tmp.'h (tempe:'.$tempe_eau.'°C, mode:'.$mode_filtration.')');
            $tmp = FILT_DUREE_SEL/60.0;
            log::add('dom4_piscine','info','Demarrage Sel pour duree:'.$tmp.' h');
            }
          }
        else if ($filtration_state == 2) {
          // Cycle filtrage en cours + Sel en cours
          $pmp_filt_am = 1;   //maintient la pompe
          $sel_am      = 1;   //maintient le sel
          if ($cur_hm >= $param_filtr["sel_end_time"]) {
            $sel_am = 0;      //Stoppe sel
            log::add('dom4_piscine','info','Fin Sel');
            if ((SEL_NBCYCLES == 1) || ($param_filtr["cptcycle_sel"] >= SEL_NBCYCLES)) {
              // Si un seul cycle de sel, ou si dernier cycle de sel, alors fin sel
              $filtration_state = 4;
              }
            else {
              // Si encore un autre cycle de sel, alors pause entre 2 cycles de sel
              $filtration_state = 3;
              $param_filtr["psel_end_pause"] = $param_filtr["sel_end_time"] + SEL_DUREE_PAUSE;
              }
            }
          }        
        else if ($filtration_state == 3) {
          //  3 : Cycle filtrage en cours + Sel en pause entre 2 cycles
          $pmp_filt_am = 1;   //maintient la pompe
          if ($cur_hm >= $param_filtr["psel_end_pause"]) {
            // fin de la pause => redemarrage d'un cycle sel
            $param_filtr["sel_end_time"] = $param_filtr["psel_end_pause"] + FILT_DUREE_SEL;
            $sel_am = 1;        //demarre sel
            $param_filtr["cptcycle_sel"] += 1; // cycle de sel suivant
            $filtration_state = 2;
            $tmp = FILT_DUREE_SEL/60.0;
            log::add('dom4_piscine','info','Demarrage Sel pour duree:'.$tmp.' h');
            }
          }
        else if ($filtration_state == 4) {
          //  4 : Cycle filtrage en cours + Sel termine
          // Fin pompe de filtration
          if ($cur_hm >= $param_filtr["filt_end_time"]) {
            $pmp_filt_am = 0; //Stoppe pompe filtration
            $filtration_state = 5;
            log::add('dom4_piscine','info','Fin pompe');
            }
          }
        }
        
      // Gestion du mode "HIVER"
      // -----------------------
      else if ($mode_filtration == FILT_HIVER) {
        // Demarrage du cycle a 2h00 
        if (($filtration_state == 0) || (($heure == 2) && ($minute == 0))) {
          // Initialisation du cycle quotidien a 2h00
          $param_filtr["filt_start_time"] = FILT_HIV_START1;
          $param_filtr["filt_end_time"] = $param_filtr["filt_start_time"] + FILT_HIV_DUREE1;
          $filtration_state = 10;
          $param_filtr["flag_ag_onoff"] = 0;
          }

        else if ($filtration_state == 10) {
          // Attente heure de depart du cycle filtration : premiere tranche
          if ($cur_hm >= $param_filtr["filt_start_time"]) {
            $pmp_filt_am = 1; //demarre pompe filtration
            $filtration_state = 11;
            $tmp = FILT_HIV_DUREE1/60.0;
            log::add('dom4_piscine','info','Demarrage Pompe pour duree:'.$tmp.'h (tempe:'.$tempe_eau.'°C, mode:'.$mode_filtration.')');
            }
          }
        else if ($filtration_state == 11) {
          $pmp_filt_am = 1;   //maintient la pompe
          if ($cur_hm >= $param_filtr["filt_end_time"]) {
            $pmp_filt_am = 0; // Stoppe pompe filtration
            log::add('dom4_piscine','info','Fin pompe');
            // Calcule les parametres de la seconde tranche
            $param_filtr["filt_start_time"] = FILT_HIV_START2;
            $param_filtr["filt_end_time"] = $param_filtr["filt_start_time"] + FILT_HIV_DUREE2;
            $filtration_state = 12;
            $param_filtr["flag_ag_onoff"] = 0;
            }
          }
        else if ($filtration_state == 12) {
          // Attente heure de depart du cycle filtration
          if ($cur_hm >= $param_filtr["filt_start_time"]) {
            $pmp_filt_am = 1; //demarre pompe filtration
            $filtration_state = 13;
            $tmp = FILT_HIV_DUREE2/60.0 ;
            log::add('dom4_piscine','info','Demarrage Pompe pour duree:'.$tmp.'h (tempe:'.$tempe_eau.'°C, mode:'.$mode_filtration.')');
            }
          }
        else if ($filtration_state == 13) {
          $pmp_filt_am = 1;   //maintient la pompe
          if ($cur_hm >= $param_filtr["filt_end_time"]) {
            $pmp_filt_am = 0; //Stoppe pompe filtration
            log::add('dom4_piscine','info','Fin pompe');
            $filtration_state = 14;
            $param_filtr["flag_ag_onoff"] = 0;
            }
          }
        // Gestion du mode antigel en hiver
        if (($filtration_state == 10) || ($filtration_state == 12) || ($filtration_state == 14)) {
          if (($tempe_abri <= 0) && ($param_filtr["flag_ag_onoff"] == 0)) {             // tempe <  0.0 øC
            // debut du cycle antigel
            $pmp_filt_am = 1; //demarre pompe filtration
            $param_filtr["flag_ag_onoff"] = 1;
            log::add('dom4_piscine','info','demarrage pompe pour secu antigel (temp abri: '.$tempe_abri.' °C, mode:'.$mode_filtration.')');
            }
          else if (($tempe_abri >= 10) && ($param_filtr["flag_ag_onoff"] == 1)) {       // tempe > +1.0 øC
            // fin du cycle antigel
            $pmp_filt_am = 0; //Stoppe pompe filtration
            $param_filtr["flag_ag_onoff"] = 0;
            log::add('dom4_piscine','info','fin pompe pour secu antigel (temp abri: '.$tempe_abri.' °C)');
            }
          }
        }
        
      // -----------------------------
      // Gestion de la Pompe a chaleur 
      // -----------------------------
      // Gestion demarrage et fin du cycle de chauffage
      // Uniquement en mode "ETE"
      $pac_enabled = 0;
      $pac_am      = 0;
      if ($mode_pac == PAC_ARRET) {
        $pac_enabled = 0;
        $pac_am      = 0;
      }
      else if ($mode_pac == PAC_MANUEL) {
        $pac_enabled = 1;
        $pac_am      = 0;
      }
      else if (($mode_filtration == FILT_MANUEL) || ($mode_filtration == FILT_AUTO)) {
        // Demarrage du cycle a 3h00 
        if (($filtration_state == 0) || (($heure == 3) && ($minute == 0))) {
          // Initialisation du cycle quotidien a 3h00
          $param_filtr["pac_start_time"] = 0;
          $pac_enabled   = 0;
          $pac_am        = 0;
          $param_filtr["flag_pac_tempe_rising"] = 1;
          }
        else if ($filtration_state == 2) {
          // demarrage PAC 60 s apres la pompe de filtration
          if ($mode_pac != PAC_ARRET) {
            if (($cur_hm >= ($param_filtr["filt_start_time"] + 1)) && ($pac_enabled == 0)) {
              $param_filtr["pac_start_time"] = $cur_hm;
              $pac_enabled = 1;  // debut du cycle de chauffage
              log::add('dom4_piscine','info','Demarrage PAC');
              }
            }
          }
        else if ($filtration_state == 4) {
          //  4 : Cycle filtrage en cours + Sel termine
          // fin PAC 60 s avant la pompe de filtration
          if (($cur_hm >= ($param_filtr["filt_end_time"] - 1)) && ($pac_enabled == 1)) {
            $pac_enabled = 0;  // fin du cycle de chauffage
            log::add('dom4_piscine','info','Arret PAC');
            }
          // Fin pompe de filtration
          }
        }
      else if ($mode_filtration == FILT_HIVER) {
        // pas de PAC
        $pac_enabled = 0;  // pas de cycle chauffage
        $pac_am      = 0;
      }
      // Asservissement de la temperature dans les plages de fonctionnement
      if ($pac_enabled == 1) {
        if       (($tempe_eau < $tempe_consigne_pac) && ($param_filtr["flag_pac_tempe_rising"] == 1)) {
          $pac_am = 1;
        }
        else if (($tempe_eau >= $tempe_consigne_pac) && ($param_filtr["flag_pac_tempe_rising"] == 1)) {
          $pac_am = 0;
          $param_filtr["flag_pac_tempe_rising"] = 0;
        }
        else if (($tempe_eau <= ($tempe_consigne_pac - PAC_TEMPE_HYSTERESIS)) && ($param_filtr["flag_pac_tempe_rising"] == 0)) {
          $pac_am = 1;
          $param_filtr["flag_pac_tempe_rising"] = 1;
        }
      }

      // Positionnement des relais de commandes ( pompe de filtrage et traitement sel )
      $cmd_sts_flt = $eql->getCmd(null, 'st_filt');
      $cmd_sts_sel = $eql->getCmd(null, 'st_sel');
      $cmd_sts_pac = $eql->getCmd(null, 'st_pac');
      // if (test_sel == 1) {          // Cas du test sel
        // pis_active_relais (2, 1);        // => active pompe
        // pis_active_relais (3, 1);        // => active sel
        // pis_active_relais (4, 0);        // => coupure PAC
      // }
      // else if (pause_filt == 1) {  // Cas de la pause active de la filtration
        // pis_active_relais (2, 0);        // => desactive pompe
        // pis_active_relais (3, 0);        // => desactive sel
        // pis_active_relais (4, 0);        // => coupure PAC
      // }
      // else {                       // Cas normal
      $cmd_sts_flt->event($pmp_filt_am);
      $cmd_sts_sel->event($sel_am);
      $cmd_sts_pac->event($pac_enabled & $pac_am);
      if  ($pmp_filt_am == 1)                    $cmd_flt_on->execCmd();  else $cmd_flt_off->execCmd();
      if  ($sel_am      == 1)                    $cmd_sel_on->execCmd();  else $cmd_sel_off->execCmd();
      if (($pac_enabled == 1) && ($pac_am == 1)) $cmd_pac_on->execCmd();  else $cmd_pac_off->execCmd();

      // }
        
      // Sauvegarde des variables statiques
      // ----------------------------------
      $cmd_filtr_state->event($filtration_state);
      $cmd_filtr_state->setConfiguration ('param_filtration', $param_filtr);
      $cmd_filtr_state->save();
      
      // $eql->setCache('dom4_counter', "essai");
      // $cpt = $eql->getCache('dom4_counter');
      // log::add('dom4_piscine','debug','Test CPT='.$cpt);
      
    }

    // ==========================================================
    // Calcule de la duree de filtration pour le mode automatique
    // ==========================================================
    public function pis_calcule_duree_filtration ($tempe) {

      // controle de la coherence de la temperature
      if (($tempe < 0.0) || ($tempe > 40.0)) {
        log::add('dom4_piscine','error','Defaut sur la valeur de temperature:'.$tempe);
        return;
      }
      // calcule la duree de filtration
        // Voir fichier "S:\Documents\Maison\Piscine\temps_filtration.ods" => modele 3
        // Utilisation du modele : Duree = A * Temperature ^ 3 + B
        // avec A = 9 / (30^3 - 10^3 ), et B = 3 - A * 10^3
        // =>  loi en tempe^3, avec 10 deg => 3h et 30 deg=> 12h
      $a = 9.0 / (pow(30.0,3.0) - pow(10.0,3.0));
      $b = 3 - $a * pow(10.0,3.0);
      $dureef = $b + $a*pow($tempe, 3.0);
      if ($dureef > 21.0) $dureef = 21.0;
      $dureef = intval(round($dureef * 60, 0)); 
      log::add('dom4_piscine','info','Filtrage automatique: A='.$a.' - B='.$b.' - tempe='.$tempe. ' - Durée filtration='.$dureef. ' mn');
      return ($dureef);
    }




   // =========================================
   // Fonction de gestion du mode de filtration
   // =========================================
    public function set_mode_filtration($new_mode) {
      $cmd = $this->getCmd(null, 'modef_value');
      if (is_object($cmd)) {
       $cmd->event($new_mode);
      }
    }

   // =================================================
   // Fonction de gestion du mode de la pompe a chaleur
   // =================================================
    public function set_mode_pac($new_mode) {
      $cmd = $this->getCmd(null, 'modep_value');
      if (is_object($cmd)) {
       $cmd->event($new_mode);
      }
    }



}

class dom4_piscineCmd extends cmd {
    /*     * *************************Attributs****************************** */
    
    /*
      public static $_widgetPossibility = array();
    */
    
    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    // Exécution d'une commande  
    public function execute($_options = array()) {
      
      // Traitement des commandes actions
      $eqLogic = $this->getEqLogic();
      // Set mode filtration
      if ($this->getLogicalId() == 'modef_arret') {
        $eqLogic->set_mode_filtration(FILT_ARRET);
      }
      else if ($this->getLogicalId() == 'modef_manuel') {
        $eqLogic->set_mode_filtration(FILT_MANUEL);
      }
      else if ($this->getLogicalId() == 'modef_auto') {
        $eqLogic->set_mode_filtration(FILT_AUTO);
      }
      else if ($this->getLogicalId() == 'modef_hiver') {
        $eqLogic->set_mode_filtration(FILT_HIVER);
      }
      // Set mode pompe a chaleur
      else if ($this->getLogicalId() == 'modep_arret') {
        $eqLogic->set_mode_pac(PAC_ARRET);
      }
      else if ($this->getLogicalId() == 'modep_tfilt') {
        $eqLogic->set_mode_pac(PAC_TFILT);
      }
      else if ($this->getLogicalId() == 'modep_teten') {
        $eqLogic->set_mode_pac(PAC_TETEN);
      }
      else if ($this->getLogicalId() == 'modep_manuel') {
        $eqLogic->set_mode_pac(PAC_MANUEL);
      }
      else if ($this->getLogicalId() == 'pac_tconsigne') {
        $new_tempe = $_options['slider'];
        $eqLogic = $this->getEqLogic();
        $cmd_ass = $eqLogic->getCmd(null, 'tconsigne_val');
        if (is_object($cmd_ass)) {
          $cmd_ass->event($new_tempe);
        }
      }
        
    }

    /*     * **********************Getteur Setteur*************************** */
}

