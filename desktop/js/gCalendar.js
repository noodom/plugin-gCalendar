
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

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td><span class="cmdAttr" data-l1key="id" ></span></td>';
    tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 100px;"></td>';
    tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="calendarUrl" style="width : 98%;"></td>';
    tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="defaultValue" style="width : 98%;"></td>';
	tr += '<td><select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="viewStyle" style="width : 98%;">';
		tr += '<option value=\'current_titleOnly\'> {{event courant}} </option>';
		tr += '<option value=\'current_withHour\'> {{event courant (avec heures)}} </option>';
		tr += '<option value=\'1day_next1hour\'> {{event heure à venir}} </option>';
		tr += '<option value=\'1day_today\'> {{event sur la journée}} </option>';
	tr += '</select></td>';
	tr += '<td><select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="indicDebFin" style="width : 98%;">';
		tr += '<option value=\'0\'> {{non}} </option>';
		tr += '<option value=\'1\'> {{oui}} </option>';
	tr += '</select></td>';
	tr += '<td><span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}</span></td>';
    tr += '<td style="width : 100px;">';
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
/*
function gCalendar_selectOpt_viewType() {
	var myOpt = [['current_titleOnly','{{event courant}}'],
				['current_withHour','{{event courant (avec heures)}}'],
				['1day_next1hour','{{event heure à venir}}'],
				['1day_today','{{event sur la journée}}']];
	var viewOpt = '';
	for(var i=0;i<myOpt.length;i++) {
		viewOpt += '<option value=\''+ myOpt[i][0] +'\'>'+ myOpt[i][1] +'</option>';
	}
	return viewOpt;
}

function gCalendar_selectOpt_indic() {
	var myOpt = [[0,'{{non}}'],[1,'{{oui}}']];
	var viewOpt = '';
	for(var i=0;i<myOpt.length;i++) {
		viewOpt += '<option value=\''+ myOpt[i][0] +'\'>'+ myOpt[i][1] +'</option>';
	}
	return viewOpt;
}
*/