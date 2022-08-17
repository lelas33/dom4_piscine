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


define("POOL_HISTORY_FILE", "/../../data/pool_log.txt");

global $pool_dt;

// ===================================================================
// Fonction de lecture de l'historique de fonctionnement de la piscine
// ===================================================================
function get_pool_history($ts_start, $ts_end)
{
  global $pool_dt;
  
  // ouverture du fichier de log: Historique piscine
  $fn_pool = dirname(__FILE__).POOL_HISTORY_FILE;
  $fpool = fopen($fn_pool, "r");

  // lecture des donnees
  $line = 0;
  $line_all = 0;  
  $pool_dt["hist"] = [];
  if ($fpool) {
    while (($buffer = fgets($fpool, 4096)) !== false) {
      // extrait les timestamps debut et fin du trajet
      $tmp=explode(",", $buffer);
      if (count($tmp) == 5) {
        list($log_ts, $log_dur_fil, $log_dur_sel, $log_dur_pac, $log_dur_ecl) = $tmp;
        $log_tsi = intval($log_ts);
        // selectionne les trajets selon leur date depart&arrive
        if (($log_tsi>=$ts_start) && ($log_tsi<$ts_end)) {
          $pool_dt["hist"][$line] = $buffer;
          $line = $line + 1;
        }
      }
      else {
        log::add('dom4_piscine', 'error', 'Ajax:get_pool_history: Erreur dans le fichier pool_log.txt, à la ligne:'.$line_all);
      }
      $line_all = $line_all + 1;
    }
  }
  fclose($fpool);
  
  log::add('dom4_piscine', 'debug', 'Ajax:get_pool_history:nb_lines='.$line);
  return;
}

// ===================================================================
// Fonction de calcul des statistique de consommation de la piscine
// ===================================================================
function get_pool_stat()
{
  global $pool_dt;
  // calcul des statistiques par mois
  // --------------------------------
  $pool_stat["pmp"] = [[]];
  $pool_stat["sel"] = [[]];
  $pool_stat["pac"] = [[]];
  $pool_stat["ecl"] = [[]];
  // $pool_stat["cfg_cost_kwh"] = $cfg_cots_kwh;
  log::add('dom4_piscine', 'debug', 'Ajax:get_pool_stat:nb_lines='.count($pool_dt["hist"]));
  for ($id=0; $id<count($pool_dt["hist"]); $id++) {
    $tmp = explode(",", $pool_dt["hist"][$id]);
    list($log_ts, $log_dur_fil, $log_dur_sel, $log_dur_pac, $log_dur_ecl) = $tmp;
    $year  = intval(date('Y', $log_ts));  // Year => ex 2020
    $month = intval(date('n', $log_ts));  // Month => 1-12
    if (isset($pool_stat["pmp"][$year][$month])){
      $pool_stat["pmp"][$year][$month] += $log_dur_fil;
    }
    else {
      $pool_stat["pmp"][$year][$month] = $log_dur_fil;
    }
    if (isset($pool_stat["sel"][$year][$month])){
      $pool_stat["sel"][$year][$month] += $log_dur_sel;
    }
    else {
      $pool_stat["sel"][$year][$month] = $log_dur_sel;
    }
    if (isset($pool_stat["pac"][$year][$month])){
      $pool_stat["pac"][$year][$month] += $log_dur_pac;
    }
    else {
      $pool_stat["pac"][$year][$month] = $log_dur_pac;
    }
    if (isset($pool_stat["ecl"][$year][$month])){
      $pool_stat["ecl"][$year][$month] += $log_dur_ecl;
    }
    else {
      $pool_stat["ecl"][$year][$month] = $log_dur_ecl;
    }
  }
  // Ajoute quelques infos complémentaires pour utilisation par javascript
  $eqLogics = eqLogic::byType('dom4_piscine');
  $eqLogic = $eqLogics[0];
  // Ajoute quelques parametres de configuration
  if ($eqLogic->getIsEnable()) {
    $pool_stat["cost_kwh"]  = floatval($eqLogic->getConfiguration("cost_kwh"));    // Cout kWh
    $pool_stat["power_pmp"] = floatval($eqLogic->getConfiguration("power_pmp"));   // puissance pompe de filtration
    $pool_stat["power_sel"] = floatval($eqLogic->getConfiguration("power_sel"));   // puissance sel
    $pool_stat["power_pac"] = floatval($eqLogic->getConfiguration("power_pac"));   // puissance pompe a chaleur
    $pool_stat["power_ecl"] = floatval($eqLogic->getConfiguration("power_ecl"));   // puissance eclairage
  }
  return($pool_stat);
}


// =============================================================================
// Fonction de capture de l'historique complet sur une periode de temps
// Periode = 1,2,3,4 (Aujourdhui, hier, cette semaine, la semaine derniere)
// Retour : histo tempe exterieure, histo tempe exterieure, histo param piscine
// =============================================================================
function get_pool_full_history($histo_range)
{
  // definition de la periode
  if ($histo_range == 1) {       // aujourd'hui
    $debut = date("Y-m-d", time());
    $fin   = date("Y-m-d", time() + 24*3600);
  }
  else if ($histo_range == 2) {  // hier
    $debut = date("Y-m-d", time() - 24*3600);
    $fin   = date("Y-m-d", time());
  }
  else if ($histo_range == 3) {  // Cette semaine
    $ts = time();
    $jour_sem = date("N", $ts) - 1; // de 0(lun) a 6(dim)
    $ts_deb = $ts - $jour_sem*24*3600;
    $debut = date("Y-m-d", $ts_deb);
    $fin   = date("Y-m-d", $ts_deb + 7*24*3600);
  }
  else if ($histo_range == 4) {  // la semaine derniere
    $ts = time();
    $jour_sem = date("N", $ts) - 1; // de 0(lun) a 6(dim)
    $ts_deb = $ts - (7+$jour_sem)*24*3600;
    $debut = date("Y-m-d", $ts_deb);
    $fin   = date("Y-m-d", $ts_deb + 7*24*3600);
  }
  
  log::add('dom4_piscine', 'debug', 'Ajax:get_pool_full_history:debut='.$debut." / fin=".$fin);
  $pool_histo = array();

  $eqLogics = eqLogic::byType('dom4_piscine');
  $eqLogic = $eqLogics[0];
  // Historique de la temperature de l'eau
  $tempe_cmdname = str_replace('#', '', $eqLogic->getConfiguration('water_tempe'));
  $tempe_cmd  = cmd::byId($tempe_cmdname);
  if (!is_object($tempe_cmd)) {
    log::add('dom4_piscine', 'error', "Ajax:get_pool_full_history: commande de temperature de l'eau non valide");
    return;
  }
  $cmdId = $tempe_cmd->getId();
  $values = array();
  $values = history::all($cmdId, $debut, $fin);
  $idx = 0;
  foreach ($values as $value) {
    $pool_histo["wt_ts"][$idx] = strtotime($value->getDatetime());
    $pool_histo["wt_va"][$idx] = round($value->getValue(),1);
    $idx++;
  }
  // Historique de la temperature de l'air
  $tempe_cmdname = str_replace('#', '', $eqLogic->getConfiguration('air_tempe'));
  $tempe_cmd  = cmd::byId($tempe_cmdname);
  if (!is_object($tempe_cmd)) {
    log::add('dom4_piscine', 'error', "Ajax:get_pool_full_history: commande de temperature de l'air non valide");
    return;
  }
  $cmdId = $tempe_cmd->getId();
  $values = array();
  $values = history::all($cmdId, $debut, $fin);
  $idx = 0;
  foreach ($values as $value) {
    $pool_histo["at_ts"][$idx] = strtotime($value->getDatetime());
    $pool_histo["at_va"][$idx] = round($value->getValue(),1);
    $idx++;
  }
  // Historique du fonctionnement de la pompe
  $tempe_cmdname = str_replace('#', '', $eqLogic->getConfiguration('Filtration_sts'));
  $tempe_cmd  = cmd::byId($tempe_cmdname);
  if (!is_object($tempe_cmd)) {
    log::add('dom4_piscine', 'error', "Ajax:get_pool_full_history: commande de statut filtration non valide");
    return;
  }
  $cmdId = $tempe_cmd->getId();
  $values = array();
  $values = history::all($cmdId, $debut, $fin);
  $idx = 0;
  foreach ($values as $value) {
    $pool_histo["fs_ts"][$idx] = strtotime($value->getDatetime());
    $pool_histo["fs_va"][$idx] = round($value->getValue(),1);
    $idx++;
  }
  // Historique du fonctionnement du traitement sel
  $tempe_cmdname = str_replace('#', '', $eqLogic->getConfiguration('SEL_sts'));
  $tempe_cmd  = cmd::byId($tempe_cmdname);
  if (!is_object($tempe_cmd)) {
    log::add('dom4_piscine', 'error', "Ajax:get_pool_full_history: commande de statut SEL non valide");
    return;
  }
  $cmdId = $tempe_cmd->getId();
  $values = array();
  $values = history::all($cmdId, $debut, $fin);
  $idx = 0;
  foreach ($values as $value) {
    $pool_histo["ss_ts"][$idx] = strtotime($value->getDatetime());
    $pool_histo["ss_va"][$idx] = round($value->getValue(),1);
    $idx++;
  }
  // Historique du fonctionnement PAC
  $tempe_cmdname = str_replace('#', '', $eqLogic->getConfiguration('PAC_sts'));
  $tempe_cmd  = cmd::byId($tempe_cmdname);
  if (!is_object($tempe_cmd)) {
    log::add('dom4_piscine', 'error', "Ajax:get_pool_full_history: commande de statut PAC non valide");
    return;
  }
  $cmdId = $tempe_cmd->getId();
  $values = array();
  $values = history::all($cmdId, $debut, $fin);
  $idx = 0;
  foreach ($values as $value) {
    $pool_histo["ps_ts"][$idx] = strtotime($value->getDatetime());
    $pool_histo["ps_va"][$idx] = round($value->getValue(),1);
    $idx++;
  }
  // Historique du fonctionnement Eclairage
  $tempe_cmdname = str_replace('#', '', $eqLogic->getConfiguration('ECL_sts'));
  $tempe_cmd  = cmd::byId($tempe_cmdname);
  if (!is_object($tempe_cmd)) {
    log::add('dom4_piscine', 'error', "Ajax:get_pool_full_history: commande de statut Eclairage non valide");
    return;
  }
  $cmdId = $tempe_cmd->getId();
  $values = array();
  $values = history::all($cmdId, $debut, $fin);
  $idx = 0;
  foreach ($values as $value) {
    $pool_histo["es_ts"][$idx] = strtotime($value->getDatetime());
    $pool_histo["es_va"][$idx] = round($value->getValue(),1);
    $idx++;
  }

  return($pool_histo);
}


// =====================================
// Gestion des commandes recues par AJAX
// =====================================
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    // Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    //  En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    //  En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
    ajax::init();

    if (init('action') == 'getPoolStat') {
      log::add('dom4_piscine', 'info', 'Ajax:getPoolStat');
      get_pool_history(0, time());  // intervalle de l'origine des temps a maintenant
      $pool_stat = get_pool_stat();
      $ret_json = json_encode ($pool_stat);
      ajax::success($ret_json);
    }
    // else if (init('action') == 'getPoolData') {
      // log::add('dom4_piscine', 'info', 'Ajax:getPoolData');
      // $ts_start = init('param')[0];
      // $ts_end   = init('param')[1];
      // log::add('dom4_piscine', 'debug', 'ts_start:'.$ts_start.' / ts_end:'.$ts_end);
      // Param 0 et 1 sont les timestamp de debut et fin de la periode de log demandée
      // get_pool_history(intval ($ts_start), intval ($ts_end));
      // $ret_json = json_encode ($pool_dt);
      // ajax::success($ret_json);
    // }
    else if (init('action') == 'getPoolFullHistory') {
      $histo_range = init('range');
      log::add('dom4_piscine', 'info', "Ajax:getPoolFullHistory pour la plage:".$histo_range);
      $pool_histo = get_pool_full_history($histo_range);
      $ret_json = json_encode ($pool_histo);
      ajax::success($ret_json);
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

