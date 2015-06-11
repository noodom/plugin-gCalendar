
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

// pour l'ajout de commande //
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td><span class="cmdAttr" data-l1key="id" ></span></td>';
    tr += '<td style="font-size:90%;">';
		tr += '{{Nom}} : <br/><input class="cmdAttr form-control input-sm" style="width:50%; margin-bottom:3px;" data-l1key="name">';
		tr += '{{URL de l\'agenda}} : <br/><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="calendarUrl" style="width:98%;">';
	tr += '</td>';
	tr += '<td style="font-size:90%;">';
		tr += '{{Valeur par défaut}} : <br/><input class="cmdAttr form-control input-sm" style="width:95%; margin-bottom:3px;" data-l1key="configuration" data-l2key="defaultValue">';
		tr += '{{Format donnée}} : <br/><select class="cmdAttr form-control input-sm" style="width:95%; margin-bottom:3px;" data-l1key="configuration" data-l2key="viewStyle">';
			tr += '<option value=\'current_titleOnly\'> {{event courant}} </option>';
			tr += '<option value=\'current_withHour\'> {{event courant (avec heures)}} </option>';
			tr += '<option value=\'1day_next1hour\'> {{event heure à venir}} </option>';
			tr += '<option value=\'1day_today\'> {{event sur la journée}} </option>';
		tr += '</select>';
		tr += '<input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="configuration" data-l2key="indicDebFin" data-label-text="{{Indicateurs début/fin}}" checked/> ';
//		tr += '<input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="useOffset"/> : {{Activer l\'offset en cas de congé/absence}} <br/>';
	tr += '</td>';
	tr += '<td style="font-size:90%;">';
		tr += '<input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="isVisible" data-label-text="{{Afficher calendrier}}" checked/> ';
		tr += '<input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="configuration" data-label-text="{{Afficher heure}}" data-l2key="showHour" checked/> ';
		tr += '<span style="padding-left:10px;" class="spanShowHour24"><input type="checkbox" class="cmdAttr" data-label-text="{{Afficher heure event de 24h}}" data-l1key="configuration" data-l2key="showHour24H" checked/></span> ';
	tr += '</td>';
    tr += '<td style="width:100px;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="info" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" value="string" style="display : none;">';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
}

// pour l'aide à l'écriture d'un événement dans l'agenda google //
$('#bt_helpForWriteGCalEvent').on('click', function (event) {
	$('#md_modal').dialog({title: "{{Assistance à la création d'un événement \"scénario\" dans un Agenda Google}}"});
	$('#md_modal').load('index.php?v=d&plugin=gCalendar&modal=help-scenario&id=' + $('.eqLogicAttr[data-l1key=id]').value(), function () {
		$('#bt_genEventFormat').on('click', function (event) {
			sEventFormat = "sc=" + $('#gCalendar_idScenario').value();
			if (($('#gCalendar_variable').value()!='') && (($('#gCalendar_valFirst').value()!='') || ($('#gCalendar_valLast').value()!=''))) {
				sEventFormat += ";" + $('#gCalendar_texte').value().replace(";", ",");
				sEventFormat += ";" + $('#gCalendar_variable').value().replace(";", ",");
				sEventFormat += ";" + $('#gCalendar_valFirst').value().replace(";", ",");
				sEventFormat += ";" + $('#gCalendar_valLast').value().replace(";", ",");
			} else if ($('#gCalendar_texte').value()!='') {
				sEventFormat += ";" + $('#gCalendar_texte').value().replace(";", ",");
			}
			$('#gCalendar_EventFormat').value(sEventFormat);
		});
		$('.btn-danger[data-dismiss=modal]').on('click', function () {
			$('#md_modal').dialog('close');
		});
	}).dialog('open');
});

$('body').delegate('.cmd .cmdAttr[data-l1key=configuration][data-l2key=showHour]', 'change', function () {
	if ($(this).value()==1) {
		$(this).closest('.cmd').find('.spanShowHour24').show();
	} else {
		$(this).closest('.cmd').find('.spanShowHour24').hide();
	}
});


//$('.eqLogicAttr[data-l2key=showHour]').on('change', function (event) { alert(''); });
