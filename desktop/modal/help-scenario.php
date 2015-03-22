<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div class="form-horizontal">
	<div class="alert alert-info" style="font-size:90%;">
		{{Cette fenêtre vous permet de créer le format de l'événement dans Google Agenda, pour pouvoir lancer automatiquement des scénarios Jeedom à partir de Google.<br/><br/>
		Le format attendu doit être du type : sc=id_scenario;texte_information;variable_scenario;valeur_variable_1erMin;valeur_variable_DernièreMin <br/>
		 -- Remarque : les valeurs du texte d'information et des variables ne doivent pas contenir de point-virgule (;). }}
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label" >{{Nom du scénario}}</label>
		<div class="col-sm-3">
			<select id="gCalendar_idScenario" class="eqLogicAttr form-control">
<?php
foreach (scenario::all() as $_oScenario) {
	echo '<option value="' . $_oScenario->getId() . '">[' . (($_oScenario->getGroup()=='')?'Aucun':$_oScenario->getGroup()). '] '. $_oScenario->getName() . '</option>';
}
?>
			</select>
		</div>
		<span>({{Valeur obligatoire}})</span>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">{{Texte d'information}}</label>
		<div class="col-sm-3">
			<input type="text" id="gCalendar_texte" class="eqLogicAttr form-control"/>
		</div>
		<span>({{Valeur non obligatoire}}, {{s'affiche sur le calendrier à coté du nom du scénario}})</span>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label" >{{Nom de la variable}}</label>
		<div class="col-sm-3">
			<select id="gCalendar_variable" class="eqLogicAttr form-control">
                <option value="">{{Aucune}}</option>
<?php
foreach (utils::o2a(dataStore::byTypeLinkId('scenario')) as $_aVarScenario) {
	echo '<option value="' . $_aVarScenario['key'] . '">' . $_aVarScenario['key']. '</option>';
}
?>
			</select>
		</div>
		<span>({{Variable obligatoire, si la valeur de début et/ou de fin est renseignée}})</span>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">{{Valeur variable 1ère minute}}</label>
		<div class="col-sm-3">
			<input type="text" id="gCalendar_valFirst" class="eqLogicAttr form-control"/>
		</div>
		<span>({{Valeur non obligatoire}} ; {{permet d'envoyer une valeur à la variable utilisée dans le scénario, pour la 1ère minute de la période}})</span>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">{{Valeur variable dernière minute}}</label>
		<div class="col-sm-3">
			<input type="text" id="gCalendar_valLast" class="eqLogicAttr form-control"/>
		</div>
		<span>({{Valeur non obligatoire}} ; {{permet d'envoyer une valeur à la variable utilisée dans le scénario, pour la dernière minute de la période}})</span>
	</div>
	<div class="form-group">
		<div class="col-sm-2" style="text-align:right;">
			<a class="btn btn-success eqLogicAction" id="bt_genEventFormat"><i class="fa fa-check-circle"></i> {{Générer}}</a>
		</div>
		<div class="col-sm-3">
			<input type="text" id="gCalendar_EventFormat" class="eqLogicAttr form-control" style="background-color:#EEEEEE;"/>
		</div>
		<span style="color:#FF0000;">(<i class="fa fa-pencil"></i> {{Après génération, faite un "copier" de la trame et la "coller" dans votre titre de l'événement Google Agenda}})</span>
	</div>
	<center><a class="btn btn-danger" data-dismiss="modal"><i class="fa fa-minus-circle"></i> {{Fermer}}</a></center>
</div>
