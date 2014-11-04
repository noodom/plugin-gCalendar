<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'gCalendar');
?>

<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un agenda}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach (eqLogic::byType('gCalendar') as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName() . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <form class="form-horizontal">
            <fieldset>
                <legend>{{Général</legend>
                <div class="form-group">
                    <label class="col-lg-3 control-label">{{Nom de l'équipement}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="Nom de l'équipement gCalendar"/>
                    </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label" >{{Objet parent}}</label>
                        <div class="col-lg-8">
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
                        <label class="col-lg-4 control-label">{{Activer}}</label>
                        <div class="col-lg-1">
                            <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                        </div>
                        <label class="col-lg-4 control-label">{{Visible}}</label>
                        <div class="col-lg-1">
                            <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                        </div>
                    </div>
            </fieldset> 
        </form>

        <legend>{{GCalendar}}</legend>
        <div class="alert alert-info">{{L'URL de l'agenda google se trouve dans Paramètres>Agenda>[Agenda voulu]>Adresse privée XML}}</div>
        <a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter une commande google agenda}}</a><br/><br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{URL de l'agenda}}</th><th>{{Valeur par défaut}}</th><th></th>
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

<?php include_file('desktop', 'gCalendar', 'js', 'gCalendar'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>