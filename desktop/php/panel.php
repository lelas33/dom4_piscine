<?php
if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$date = array(
    'start' => date('Y-m-d', strtotime(config::byKey('history::defautShowPeriod') . ' ' . date('Y-m-d'))),
    'end' => date('Y-m-d'),
);
sendVarToJS('eqType', 'dom4_piscine');
sendVarToJs('object_id', init('object_id'));
$eqLogics = eqLogic::byType('dom4_piscine');

?>

<div class="row" id="div_pool">
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1" style="height: 350px;padding-top:10px">
            <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 0px 5px;background-color:#f8f8f8">
              <div class="pull-left" style="min-height: 100px;">
                <img id="piscine_img" src=<?php echo "plugins/dom4_piscine/desktop/php/piscine-img.jpg"; ?> width="1200" />
              </div>
            </fieldset>
        </div>
    </div>
    <div>
      <div class="row">
      <div class="col-lg-10 col-lg-offset-1" style="padding-top:10px">
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#pool_info_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Semaine en cours}}</a></li>
          <li role="presentation"><a href="#pool_stat_tab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Statistiques}}</a></li>
        </ul>
      </div>
      </div>
      <div class="row">
      <div class="tab-content" style="height:1200px;">
        <div role="tabpanel" class="tab-pane" id="pool_info_tab">
          <div class="row">
            <div class="col-lg-10 col-lg-offset-1" style="height: 110px;padding-top:10px;">
              <form class="form-horizontal">
                <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 0px 5px;background-color:#f8f8f8">
                  <div style="min-height: 10px;">
                  </div>
                  <div style="min-height:40px;font-size: 1.5em;">
                    <i style="font-size: initial;"></i> {{Période analysée}}
                  </div>
                  <div style="min-height:50px;">
                    <div style="padding-top:10px;font-size: 1.5em;">
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpool_per_today'>{{Aujourd'hui}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpool_per_yesterday'>{{Hier}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpool_per_this_week'>{{Cette semaine}}</a>
                      <a style="margin-right:5px;" class="pull-left btn btn-success btn-sm tooltips" id='btpool_per_prev_week'>{{La semaine derniere}}</a>
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
            <div class="col-lg-2">
            </div>
          </div>
          <div class="row">
            <div class="col-lg-10 col-lg-offset-1" style="height: 150px;padding-top:10px;">
              <form class="form-horizontal">
                <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                   <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                       <i style="font-size: initial;"></i> {{Informations pour la semaine en cours}}
                   </div>
                   <div style="min-height: 30px;">
                     <img src="plugins/dom4_piscine/desktop/php/piscine_nuit_eclairage.jpg"; width="150" />
                     <i style="font-size: 1.5em;">{{Températures}}</i>
                   </div>
                   <div id='div_graph_info_tempe' style="font-size: 1.2em;"></div>
                   <div style="min-height: 30px;">
                     <img src="plugins/dom4_piscine/desktop/php/pompe_filtration_piscine.jpg"; width="150" />
                     <i style="font-size: 1.5em;">{{Filtration / Traitement / Chauffage}}</i>
                   </div>
                   <div id='div_graph_info_filt' style="font-size: 1.2em;"></div>
                </fieldset>
                <div style="min-height: 10px;"></div>
              </form>
            </div>
            <div class="col-lg-2">
            </div>
          </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="pool_stat_tab">
          <div class="row">
              <div class="col-lg-8 col-lg-offset-2" style="padding-top:10px">
                <form class="form-horizontal">
                     <fieldset style="border: 1px solid #e5e5e5; border-radius: 5px 5px 5px 5px;background-color:#f8f8f8">
                         <div style="padding-top:10px;padding-left:24px;padding-bottom:10px;color: #333;font-size: 1.5em;">
                             <i style="font-size: initial;"></i> {{Statistiques par mois de consommation électrique}}
                         </div>
                         <div style="min-height: 30px;">
                           <img src="plugins/dom4_piscine/desktop/php/pompe_filtration_piscine.jpg"; width="150" />
                           <img src="plugins/dom4_piscine/desktop/php/cellule_electrolyseur.jpg"; width="150" />
                           <img src="plugins/dom4_piscine/desktop/php/pompe_a_chaleur.jpg"; width="150" />
                           <img src="plugins/dom4_piscine/desktop/php/piscine_nuit_eclairage.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Consommation totale}}</i>
                         </div>
                         <div id='div_graph_stat_total' style="font-size: 1.2em;"></div>
                         <div style="min-height: 30px;">
                           <img src="plugins/dom4_piscine/desktop/php/pompe_filtration_piscine.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Pompe de filtration}}</i>
                         </div>
                         <div id='div_graph_stat_pmp' style="font-size: 1.2em;"></div>
                         <div style="min-height: 30px;">
                           <img src="plugins/dom4_piscine/desktop/php/cellule_electrolyseur.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Traitement au sel}}</i>
                         </div>
                         <div id='div_graph_stat_sel' style="font-size: 1.2em;"></div>
                         <div style="min-height: 30px;">
                           <img src="plugins/dom4_piscine/desktop/php/pompe_a_chaleur.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Pompe à chaleur}}</i>
                         </div>
                         <div id='div_graph_stat_pac' style="font-size: 1.2em;"></div>
                         <div style="min-height: 30px;">
                           <img src="plugins/dom4_piscine/desktop/php/piscine_nuit_eclairage.jpg"; width="150" />
                           <i style="font-size: 1.5em;">{{Eclairage du bassin}}</i>
                         </div>
                         <div id='div_graph_stat_ecl' style="font-size: 1.2em;"></div>
                     </fieldset>
                     <div style="min-height: 10px;"></div>
                 </form>
              </div>
          </div>
        </div>
      </div>
    </div>
    </div>
</div>
<?php include_file('desktop', 'panel', 'js', 'dom4_piscine');?>
