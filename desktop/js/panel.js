
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
 var globalEqLogic = $("#eqlogic_select option:selected").val();
 var isCoutVisible = false;
$(".in_datepicker").datepicker();

// Variables partagées

const YEAR_COLOR_NAMES = [
  "Aquamarine",
  "Blue",
  "BlueViolet",
  "Brown",
  "Chocolate",
  "Coral",
  "Cyan",
  "DarkBlue",
  "DarkCyan",
  "Aqua",
  "DarkGrey",
  "DarkRed",
  "DarkSalmon",
  "DimGrey",
  "Fuchsia",
  "Gold",
  "Green",
  "GreenYellow",
  "Indigo",
  "LightGreen",
  "LightPink",
  "LightSalmon",
  "LightYellow",
  "Lime",
  "Magenta",
  "MidnightBlue",
  "Orange",
  "OrangeRed",
  "Orchid",
  "PaleGoldenRod",
  "Pink",
  "Purple",
  "Red",
  "RoyalBlue",
  "Salmon",
  "SkyBlue",
  "Tomato",
  "Turquoise",
  "Violet",
  "White",
  "Yellow"
];

const NB_TRIPS_COLORS = YEAR_COLOR_NAMES.length;
var pool_stat_loaded = 0;
var pool_info_loaded = 0;

const DAY_NAME = ["Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"];

// Fonctions realisées au chargement de la page: charger les données sur la période par défaut,
// et afficher les infos correspondantes
// ============================================================================================
$(document).ready(function() {
  // Show pool stats
  $(".tab_content").hide(); //Hide all content
  $("#pool_info_tab").addClass("active").show(); //Activate first tab
  cmd_get_histo(1);
  pool_info_loaded = 1;

});

// Sélection des différents onglets
// ================================
$('.nav li a').click(function(){

	var selected_tab = $(this).attr("href");
  
  if (selected_tab == "#pool_stat_tab") {
    if (pool_stat_loaded == 0) {
      loadStats();
      pool_stat_loaded = 1;
    }
  }
  else if (selected_tab == "#pool_info_tab") {
    if (pool_info_loaded == 0) {
      cmd_get_histo(1);
      pool_info_loaded = 1;
    }
  }

});


// =======================================================================
//       Gestion des infos de controle de la piscine sur une periode
// =======================================================================

// gestion du bouton de definition et de mise à jour de la période pour les trajets
// ================================================================================
// Aujourd'hui
$('#btpool_per_today').on('click',function(){
  cmd_get_histo(1);
});
// Hier
$('#btpool_per_yesterday').on('click',function(){
  cmd_get_histo(2);
});
// Cette semaine
$('#btpool_per_this_week').on('click',function(){
  cmd_get_histo(3);
});
// Les 7 derniers jours
$('#btpool_per_prev_week').on('click',function(){
  cmd_get_histo(4);
});


// Interrogation serveur pour historique sur une periode de temps
// ==============================================================
function cmd_get_histo(range_param) {
  $.ajax({
    type: "POST",
    url: 'plugins/dom4_piscine/core/ajax/dom4_piscine.ajax.php',
    data: {
      action: "getPoolFullHistory",
      range: range_param,
    },
    dataType: 'json',
    error: function (request, status, error) {
      alert("loadData:Error"+status+"/"+error);
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      console.log("[cmd_get_histo] Historique objet récupéré");
      if (data.state != 'ok') {
          $('#div_alert').showAlert({message: data.result, level: 'danger'});
          return;
      }
      console.log("historique:"+data.result);
      histo_data = JSON.parse(data.result);
      display_histo(histo_data);
    }
  });
}

// Génération des graphes d'historique sur une periode de temps
// ============================================================
function display_histo(histo_data) {

  // Mise en forme des donnees du premier graphe
  var wt_serie =  {
      name: "Tempé. Eau",
      color: "Aquamarine",
      data: []
    };
  var at_serie =  {
      name: "Tempé. Air",
      color: "Green",
      data: []
    };
  if (histo_data.wt_ts) {
    console.log("Nb points wt_serie:"+histo_data.wt_ts.length);
    for (idx=0; idx<histo_data.wt_ts.length; idx++) {
      wt_serie.data.push([parseInt(histo_data.wt_ts[idx])*1000, parseFloat(histo_data.wt_va[idx])]);
    }
    
  }
  if (histo_data.at_ts) {
    console.log("Nb points at_serie:"+histo_data.at_ts.length);
    for (idx=0; idx<histo_data.at_ts.length; idx++) {
      at_serie.data.push([parseInt(histo_data.at_ts[idx])*1000, parseFloat(histo_data.at_va[idx])]);
    }
  }
  // console.log("wt_serie:"+wt_serie.name);
  // console.log("wt_serie:"+wt_serie.data[70]);

  // Mise en forme des donnees du second graphe
  var fs_serie =  {
      name: "Filtration",
      step: 'left', // or 'center' or 'right'
      color: "Aquamarine",
      data: []
    };
  var ss_serie =  {
      name: "Traitement SEL",
      step: 'left', // or 'center' or 'right'
      color: "Red",
      data: []
    };
  var ps_serie =  {
      name: "Pompe à chaleur",
      step: 'left', // or 'center' or 'right'
      color: "Blue",
      data: []
    };
  var es_serie =  {
      name: "Eclairage",
      step: 'left', // or 'center' or 'right'
      color: "Yellow",
      data: []
    };
  if (histo_data.fs_ts) {
    console.log("Nb points fs_serie:"+histo_data.fs_ts.length);
    for (idx=0; idx<histo_data.fs_ts.length; idx++) {
      fs_serie.data.push([parseInt(histo_data.fs_ts[idx])*1000, 5.0+parseFloat(histo_data.fs_va[idx])]);
    }
  }
  if (histo_data.ss_ts) {
    console.log("Nb points ss_serie:"+histo_data.ss_ts.length);
    for (idx=0; idx<histo_data.ss_ts.length; idx++) {
      ss_serie.data.push([parseInt(histo_data.ss_ts[idx])*1000, 3.5+parseFloat(histo_data.ss_va[idx])]);
    }
  }
  if (histo_data.ps_ts) {
    console.log("Nb points ss_serie:"+histo_data.ps_ts.length);
    for (idx=0; idx<histo_data.ps_ts.length; idx++) {
      ps_serie.data.push([parseInt(histo_data.ps_ts[idx])*1000, 2.0+parseFloat(histo_data.ps_va[idx])]);
    }
  }
  if (histo_data.es_ts) {
    console.log("Nb points ss_serie:"+histo_data.es_ts.length);
    for (idx=0; idx<histo_data.es_ts.length; idx++) {
      es_serie.data.push([parseInt(histo_data.es_ts[idx])*1000, 0.5+parseFloat(histo_data.es_va[idx])]);
    }
  }

  Highcharts.setOptions({
    global: {
        timezoneOffset: -2 * 60
    }
  });
  // Temperature de l'eau et temperature de l'air
  Highcharts.chart('div_graph_info_tempe', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'spline'
      },
      title: {
          text: ''
      },
      xAxis: {
          type: 'datetime',
          dateTimeLabelFormats: { // don't display the dummy year
              month: '%e. %b',
              year: '%b'
          },
          title: {
              text: 'Date'
          }
      },
      yAxis: {
          // min: 0,
          title: {
              text: 'Température (°C)'
          }
      },
      tooltip: {
        headerFormat: '<b>{series.name}</b><br>',
        pointFormat: '{point.x:%e. %b - %H:%M}: {point.y:.1f} °C'
      },
      plotOptions: {
        spline: {
          lineWidth: 4,
          marker: {
            enabled: false
          }
        }
      },
      series: [wt_serie, at_serie]
  });

  // Temperature filtration / Sel / PAC / Eclairage
  Highcharts.chart('div_graph_info_filt', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'line'
      },
      title: {
          text: ''
      },
      xAxis: {
          type: 'datetime',
          dateTimeLabelFormats: { // don't display the dummy year
              month: '%e. %b',
              year: '%b'
          },
          title: {
              text: 'Date'
          }
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Eclairage / PAC / Sel / Filtration'
          }
      },
      tooltip: {
        headerFormat: '<b>{series.name}</b><br>',
        pointFormat: '{point.x:%e. %b - %H:%M}'
      },
      plotOptions: {
        line: {
          lineWidth: 4,
          marker: {
            enabled: false
          }
        }
      },
      series: [fs_serie, ss_serie, ps_serie, es_serie]
  });

}

// =======================================================================
//     Gestion des statistiques des infos de controle de la piscine 
// =======================================================================

// capturer les donnees depuis le serveur sur la totalite de l'historique
// ======================================================================
function loadStats(){
    var param = [];
    $.ajax({
        type: 'POST',
        url: 'plugins/dom4_piscine/core/ajax/dom4_piscine.ajax.php',
        data: {
            action: 'getPoolStat',
            param: param
        },
        dataType: 'json',
        error: function (request, status, error) {
            alert("loadData:Error"+status+"/"+error);
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            console.log("[loadStats] Objet statistique récupéré");
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            console.log("stat_dt:"+data.result);
            stat_data = JSON.parse(data.result);
            pool_stats(stat_data);
        }
    });
}


// Génération des graphes de statistiques pour l'ensemble des donnees
// ==================================================================
function pool_stats(stat_data) {

  // recuperation des infos de configuration
  var cfg_cost_kwh = stat_data.cost_kwh;
  var cfg_power_pmp = stat_data.power_pmp;
  var cfg_power_sel = stat_data.power_sel;
  var cfg_power_pac = stat_data.power_pac;
  var cfg_power_ecl = stat_data.power_ecl;

  // mise en forme des donnnes
  var sum_duree = [];
  var dt_pmp = [[]];
  var dt_sel = [[]];
  var dt_pac = [[]];
  var dt_ecl = [[]];
  var dt_total = [[]];
  for (y=2020; y<2040; y++) {
    sum_duree[y] = 0;
    dt_pmp[y] = [];
    dt_sel[y] = [];
    dt_pac[y] = [];
    dt_ecl[y] = [];
    dt_total[y] = [];
    for (m=1; m<=12; m++) {
      // Pompe filtration
      dt_pmp[y][m-1] = 0;
      if (stat_data.pmp[y] != null)
        if (stat_data.pmp[y][m] != null)
          dt_pmp[y][m-1] = Math.round(stat_data.pmp[y][m]/60.0);
        else
          dt_pmp[y][m-1] = 0;
      // somme duree pompe
      sum_duree[y] += dt_pmp[y][m-1];
      // Sonde SEL
      dt_sel[y][m-1] = 0;
      if (stat_data.sel[y] != null)
        if (stat_data.sel[y][m] != null)
          dt_sel[y][m-1] = Math.round(stat_data.sel[y][m]/60.0);
        else
          dt_sel[y][m-1] = 0;
      // Pompe a chaleur
      dt_pac[y][m-1] = 0;
      if (stat_data.pac[y] != null)
        if (stat_data.pac[y][m] != null)
          dt_pac[y][m-1] = Math.round(stat_data.pac[y][m]/60.0);
        else
          dt_pac[y][m-1] = 0;
      // Eclairage
      dt_ecl[y][m-1] = 0;
      if (stat_data.ecl[y] != null)
        if (stat_data.ecl[y][m] != null)
          dt_ecl[y][m-1] = Math.round(stat_data.ecl[y][m]/60.0);
        else
          dt_ecl[y][m-1] = 0;
      // Consommation totale
      dt_total[y][m-1] = 0;
      if ((stat_data.pmp[y] != null) && (stat_data.pmp[y][m] != null))
          dt_total[y][m-1] = Math.round(cfg_power_pmp * stat_data.pmp[y][m]/60.0);
      if ((stat_data.sel[y] != null) && (stat_data.sel[y][m] != null))
          dt_total[y][m-1] += Math.round(cfg_power_sel * stat_data.sel[y][m]/60.0);
      if ((stat_data.pac[y] != null) && (stat_data.pac[y][m] != null))
          dt_total[y][m-1] += Math.round(cfg_power_pac * stat_data.pac[y][m]/60.0);
      if ((stat_data.ecl[y] != null) && (stat_data.ecl[y][m] != null))
          dt_total[y][m-1] += Math.round(cfg_power_ecl * stat_data.ecl[y][m]/60.0);
    }
  }
  // console.log("dt_2022:"+dt_dist[2021]);
  // console.log("sum_duree:"+sum_duree);
  // console.log("dt_pmp_2022:"+dt_pmp[2022]);
  // console.log("dt_total_2022:"+dt_total[2022]);

  // mise au format attendu par highcharts
  tot_series = [];
  pmp_series = [];
  sel_series = [];
  pac_series = [];
  ecl_series = [];
  for (y=2020; y<2040; y++) {
    var serie_total = {
      name: y,
      color: YEAR_COLOR_NAMES[y-2020],
      data: dt_total[y]
    };
    var serie_pmp = {
      name: y,
      color: YEAR_COLOR_NAMES[y-2020],
      data: dt_pmp[y]
    };
    var serie_sel = {
      name: y,
      color: YEAR_COLOR_NAMES[y-2020],
      data: dt_sel[y]
    };
    var serie_pac = {
      name: y,
      color: YEAR_COLOR_NAMES[y-2020],
      data: dt_pac[y]
    };
    var serie_ecl = {
      name: y,
      color: YEAR_COLOR_NAMES[y-2020],
      data: dt_ecl[y]
    };
    if (sum_duree[y] != 0) {
      tot_series.push(serie_total);
      pmp_series.push(serie_pmp);
      sel_series.push(serie_sel);
      pac_series.push(serie_pac);
      ecl_series.push(serie_ecl);
    }
  }
  // console.log("pmp_series:"+dt_pmp);

  // Consommation totale
  Highcharts.chart('div_graph_stat_total', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Consom. (kWh) / Coût (€)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' kWh / ' + Math.round(10*point.y*cfg_cost_kwh)/10 + ' €</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: tot_series
  });

  // Consommation pompe de filtration
  var hr_to_cost_pmp = cfg_cost_kwh * cfg_power_pmp;
  Highcharts.chart('div_graph_stat_pmp', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Durée (hr) / Conso. (kWh) / Coût (€)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' hr / ' + Math.round(10*point.y*cfg_power_pmp)/10 + ' kWh / ' + Math.round(10*point.y*hr_to_cost_pmp)/10 + ' €</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: pmp_series
  });

  // Consommation traitement sel
  var hr_to_cost_sel = cfg_cost_kwh * cfg_power_sel;
  Highcharts.chart('div_graph_stat_sel', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Durée (hr) / Conso. (kWh) / Coût (€)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' hr / ' + Math.round(10*point.y*cfg_power_sel)/10 + ' kWh / ' + Math.round(10*point.y*hr_to_cost_sel)/10 + ' €</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: sel_series
  });

  // Consommation pompe a chaleur
  var hr_to_cost_pac = cfg_cost_kwh * cfg_power_pac;
  Highcharts.chart('div_graph_stat_pac', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Durée (hr) / Conso. (kWh) / Coût (€)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' hr / ' + Math.round(10*point.y*cfg_power_pac)/10 + ' kWh / ' + Math.round(10*point.y*hr_to_cost_pac)/10 + ' €</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: pac_series
  });

  // Consommation eclairage
  var hr_to_cost_ecl = cfg_cost_kwh * cfg_power_ecl;
  Highcharts.chart('div_graph_stat_ecl', {
      chart: {
          plotBackgroundColor:'#808080',
          type: 'column'
      },
      title: {
          text: ''
      },
      xAxis: {
          title: {
              text: 'Mois'
          },
          categories: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec']
      },
      yAxis: {
          min: 0,
          title: {
              text: 'Durée (hr) / Conso. (kWh) / Coût (€)'
          }
      },
      tooltip: {
          shared: true,
          useHTML: true,
          formatter: function () {
              return this.points.reduce(function (s, point) {
                  var hdr  = '<br/><span style="color:'+ point.series.color +';font-size:14px"><b>' + point.series.name + ': </b></span>';
                  var data = '<span style="font-size:14px">'+point.y + ' hr / ' + Math.round(10*point.y*cfg_power_ecl)/10 + ' kWh / ' + Math.round(10*point.y*hr_to_cost_ecl)/10 + ' €</span>';
                  return (s + hdr + data);
              }, '<span style="font-size:16px"><b>'+this.x+'</b></span>');
          }
      },
      series: ecl_series
  });
}

