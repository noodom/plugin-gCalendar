<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'gCalendar');
$eqLogics = eqLogic::byType('gCalendar');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un agenda}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
           </ul>
       </div>
   </div>

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend>{{Mes agendas Google}}
    </legend>
    <?php
if (count($eqLogics) == 0) {
	echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez encore d'agenda Google, cliquez à gauche sur le bouton ajouter un agenda pour commencer}}</span></center>";
} else {
	?>
       <div class="eqLogicThumbnailContainer">
        <?php
foreach ($eqLogics as $eqLogic) {
		echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
		echo "<center>";
		echo '<img src="plugins/gCalendar/doc/images/gCalendar_icon.png" height="105" width="95" />';
		echo "</center>";
		echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
		echo '</div>';
	}
	?>
  </div>
  <?php }
?>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <form class="form-horizontal">
        <fieldset>
            <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
            <div class="form-group">
                <label class="col-sm-2 control-label">{{Nom de l'équipement}}</label>
                <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="Nom de l'équipement gCalendar"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" >{{Objet parent}}</label>
                <div class="col-sm-2">
                    <select class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
                   </select>
               </div>
           </div>
           <div class="form-group">
            <div class="col-sm-1">
               <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
               <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
           </div>
       </div>
       <legend></legend>
       <div class="form-group">
        <label class="col-sm-2 control-label" >{{Autre Widget}}</label>
        <div class="col-sm-2" style="width: 4%;">
            <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-l1key="configuration"  data-l2key="widgetOther"  checked/>
        </div>
        <span style="font-size: 80%;">({{A cocher si vous souhaitez utiliser un widget "personnel"; données brutes affichées dans ce cas. Laissez décocher pour utiliser le widget du plugin.}})</span>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label" >{{Ne pas afficher la date}}</label>
        <div class="col-sm-2" style="width: 4%;">
            <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-l1key="configuration"  data-l2key="hideDateDashboard"  checked/>
        </div>
        <span style="font-size: 80%;">({{Uniquement pour le Dashboard et Vues}})</span>
    </div>
    <legend></legend>
    <div class="form-group">
        <label class="col-sm-2 control-label" >{{Autorise les Scénarios}}</label>
        <div class="col-sm-2" style="width: 4%;">
            <input type="checkbox" class="eqLogicAttr" data-l1key="configuration"  data-l2key="acceptLaunchSc"  checked/>
        </div>
        <span style="font-size: 80%;">({{Permet de lancer un scénario directement depuis le plugin gCalendar. La trame reçue de l'évènement doit être correctement formatée.}})</span><br/>
        <i class="fa fa-magic cursor" style="color:#0000FF;" id="bt_helpForWriteGCalEvent"></i><span style="font-size:90%;color:#0000FF;"> : {{Aide à la saisie d'un événement dans Google}}</span>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label" >{{Fréquence de mise à jour du cache}}</label>
        <div class="col-sm-2">
            <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="refreshPeriod">
                <option value="15">15 min.</option>
                <option value="30">30 min.</option>
                <option value="60">1 h.</option>
                <option value="180">3 h.</option>
                <option value="360">6 h.</option>
                <option value="720">12 h.</option>
                <option value="1440">24 h.</option>
            </select>
        </div>
    </div>
</fieldset>
</form>

<legend>{{GCalendar}}</legend>
<div class="alert alert-info" style="font-size:90%;">
 {{L'URL de l'agenda google se trouve dans Paramètres>Agenda>[Agenda voulu]>Adresse privée XML<br/>
 <br/>
 - Pour les éléments de configuration, reportez-vous à la documentation.<br/>
 - Pour une utilisation identique à la V1 du plugin, utiliser le format de donnée "event courant".}}</div>
 <a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter une commande google agenda}}</a><br/><br/>
 <table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th style="width: 3%;">{{Id}}</th>
            <th style="width: 37%;">{{Nom et URL}}</th>
            <th style="width: 29%;">{{Données d'utilisation}}</th>
            <th style="width: 23%;">{{Options graphique}}</th>
            <th style="width: 8%;">{{Action}}</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>

<form class="form-horizontal">
    <fieldset>
        <div class="form-actions">
            <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
            <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
        </div>
    </fieldset>
</form>
</div>
</div>

<?php include_file('desktop', 'gCalendar', 'js', 'gCalendar');?>
<?php include_file('core', 'plugin.template', 'js');?>