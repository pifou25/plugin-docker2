<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('docker2');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" id="bt_syncDocker">
				<i class="fas fa-sync"></i>
				<br>
				<span>{{Synchroniser}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor logoSecondary" id="bt_editConfigFile">
				<i class="far fa-file-code"></i>
				<br>
				<span class="txtColor">{{Code}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes templates}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br/><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template n\'est paramétré, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default" id="bt_docker2Assistant"><i class="fas fa-people-carry"></i><span class="hidden-xs"> {{Assistant}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-7">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Objet parent}}</label>
								<div class="col-sm-7">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
								<div class="col-sm-7">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Options}}</label>
								<div class="col-sm-7">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Docker host}}</label>
								<div class="col-sm-7">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="docker_number">
										<?php
										for ($i = 1; $i <= config::byKey('max_docker_number', "docker2"); $i++) {
											$config = config::byKey('docker_config_' . $i, 'docker2');
											echo '<option value="' . $i . '">[' . $i . '] ' . $config['name'] . '</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom du conteneur}}</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="name" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Sauvegarder les mount}}</label>
								<div class="col-sm-7">
									<input type="checkbox" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="saveMount" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Mode de création}}</label>
								<div class="col-sm-7">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="create::mode">
										<option value="manual">{{Manuel}}</option>
										<option value="jeedom_run">{{Jeedom docker run}}</option>
										<option value="jeedom_compose">{{Jeedom docker compose}}</option>
									</select>
								</div>
							</div>
							<div class="form-group create_mode jeedom_run">
								<label class="col-sm-3 control-label">{{Commande de création}}</label>
								<div class="col-sm-7">
									<textarea rows="12" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="create::run"></textarea>
								</div>
							</div>
							<div class="form-group create_mode jeedom_compose">
								<label class="col-sm-3 control-label">{{Docker compose}}</label>
								<div class="col-sm-7">
									<textarea rows="20" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="create::compose"></textarea>
								</div>
							</div>
						</div>
						<div class="col-lg-6">

							<legend><i class="fas fa-exclamation"></i> {{Action}}</legend>
							<center>
								<span class="input-group-btn">
									<a class="btn btn-sm btn-success roundedLeft" id="bt_dockerBackup"><i class="fas fa-save"></i><span class="hidden-xs"> {{Sauvegarder le docker}}</span>
									</a><a class="btn btn-sm btn-warning" id="bt_dockerRestore"><i class="fas fa-database"></i><span class="hidden-xs"> {{Restaurer le docker}}</span>
									</a><a class="btn btn-sm btn-default" id="bt_dockerDownloadBackup"><i class="fas fa-download"></i><span class="hidden-xs"> {{Télécharger sauvegarde}}</span>
									</a><span class="btn btn-default btn-file" style="height: 28px;line-height: 14px">
										<i class="fas fa-upload"></i> {{Envoyer sauvegarde}}<input id="bt_dockerUploadBackup" type="file" name="file" data-url="plugins/docker2/core/ajax/docker2.ajax.php?action=backupupload">
									</span><a class="btn btn-sm btn-default" id="bt_dockerLog"><i class="far fa-file"></i><span class="hidden-xs"> {{Logs docker}}</span>
									</a>
								</span>
							</center>
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{ID}}</label>
								<div class="col-sm-9">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="id" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Commande}}</label>
								<div class="col-sm-9">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="command" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Créé}}</label>
								<div class="col-sm-3">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="createdAt" style="font-size : 1em"></span>
								</div>
								<label class="col-sm-3 control-label">{{Taille}}</label>
								<div class="col-sm-3">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="size" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Ports}}</label>
								<div class="col-sm-3">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="ports" style="font-size : 1em"></span>
								</div>
								<label class="col-sm-3 control-label">{{Networks}}</label>
								<div class="col-sm-3">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="networks" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Montages}}</label>
								<div class="col-sm-9">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="mounts" style="font-size : 1em"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Image}}</label>
								<div class="col-sm-9">
									<span class="eqLogicAttr tooltips label label-default" data-l1key="configuration" data-l2key="image" style="font-size : 1em"></span>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
				<hr>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th>{{Id}}</th>
								<th>{{Nom}}</th>
								<th>{{Type}}</th>
								<th>{{Paramètres}}</th>
								<th>{{Options}}</th>
								<th>{{Action}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div>

<?php include_file('desktop', 'docker2', 'js', 'docker2'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>