<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_rhum.class.php
 * \ingroup rhum
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsRhum
 */
class ActionsRhum
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			global $langs, $conf, $user,$db;
			$langs->load('rhum@rhum');
			
			// Ajout d'un droit au module commande
			include_once DOL_DOCUMENT_ROOT .'/core/modules/modCommande.class.php';
			$mod = new modCommande($db);
			$mod->rights[9][0] = 1046663;
			$mod->rights[9][1] = 'Ajouter des lignes libres aux commandes';
			$mod->rights[9][3] = 1;
			$mod->rights[9][4] = 'lignelibre';
			$mod->rights[9][5] = '';
			$mod->insert_permissions(1, null, 1);
			
			if(! empty($_POST)){
				
				$prodMode = GETPOST('prod_entry_mode');
				$idprod = GETPOST('idprod');
				$rhumerie = GETPOST('options_rhumerie');
				$rhumselect = GETPOST('rhumerie');
				$commande = GETPOST('id');
				
				/*
				 * Vérification des données envoyées côté serveur
				 */ 
				
				// ajout d'une ligne libre
				if($prodMode == 'free'){
					if(empty($user->rights->commande->lignelibre)){ // si l'utilisateur n'a pas le droit aux ligne libre
						$_POST['dp_desc'] = '';
						$_POST['type'] = -1;
						setEventMessages($langs->trans('noLibre'), null, 'errors');
						return 1;
					}
				}
				
				// ajout d'un produit prédéfini
				if($prodMode == 'predef'){
					if($idprod !== '' && $idprod !== '-1' &&  $idprod !== '0'){
						// vérifier la conf rhumerie obligatoire... si ce n'est pas obligatoire, pas besoin de bloquer
						if(!empty($conf->global->RHUMERIE_OBLIGATOIRE)){
							if($rhumselect !== null && ($rhumerie == '-1' || $rhumerie == '')){ 
								setEventMessages($langs->trans('rhumEditError'), null, 'errors');
							} elseif ($rhumselect == null){
								setEventMessages($langs->trans('noRhumerie'), null, 'errors');
							}
							return 1;
						}
					}
				}
				
				// modification d'une ligne de commande
				if($rhumerie == '-1' && !empty($conf->global->RHUMERIE_OBLIGATOIRE)){ // Vérifie la conf rhumerie obligatoire ou pas
					setEventMessages($langs->trans('rhumEditError'), null, 'errors');
					return 1;
				}
			}
			
			return 0;
			
		}
	}
	
	function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $db;
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value

		if (in_array('thirdpartycard', explode(':', $parameters['context'])))
		{
			// do something only for the context 'thirdpartycard'
			
			// compter le nombre de brasserie gérées par ce tiers
			$sql = 'SELECT COUNT(*) AS total';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'rhumerie t ';
			$sql.= ' WHERE t.fk_soc = '.$object->id;
			
			$res = $db->query($sql);
			
			if($res){
				$obj = $db->fetch_object($res);	
			}
			
			echo '<tr><td>Nombre de rhumeries gérées</td><td>'.$obj->total.'</td></tr>';
		}
		
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			
			$this->afficheNomsRhumerie();
			$this->ajoutColonneRhumerie();
			
		}

		if (! $error)
		{
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
	
	function formCreateProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			
			$this->formInit();
			$this->addCreateSelect();
			$this->verifLignesLibres();
			$this->catchErrors();
						
		}
	}
	
	
	function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			$this->formInit();
			$this->formEdit();
			$this->catchErrors();
		}
	}
	
	/**
	 * Permet d'afficher les noms des rhumeries au lieu de leurs id dans le tableau de lignes
	 */
	function afficheNomsRhumerie(){
		global $db;
		
		// récupérer la liste des rhumeries à afficher dans les td
		$sql = 'SELECT t.rowid, t.label';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'rhumerie t ';
		$sql.= ' WHERE 1';
		
		$res = $db->query($sql);
		
		if($res){
			$select = array();
			while($obj = $db->fetch_object($res)){
				$select[$obj->rowid] = $obj->label;
			}
		}
		$form = new Form($db);
		$output = $form->selectarray('rhum', $select, '',1,0,0,'style="width: 120px;"',0,20,0,'','',1);
		print '<div style="display: none;">'.$output.'</div>';
		
		?>
			
			<script type="text/javascript">
			$(document).ready(function(){
								
				// remplace l'id_rhumerie par le nom dans le tableau
				$('.commandedet_extras_rhumerie').each(function(){
					var name = $('#rhum').find('option[value="'+$(this).text()+'"]').html();
					console.log(name);
					$(this).html(name);
				});
			});
			</script>
			
		<?php
		
	}
	
	
	/**
	 * Ajoute la colonne rhumerie dans le tableau de lignes de commande
	 */
	function ajoutColonneRhumerie(){
		?>
		<script type="text/javascript">
		$(document).ready(function(){	
			// ajout de la colonne rhumerie dans le tableau des lignes de la commande
			$('<td>Rhumerie</td>').insertAfter('tr.liste_titre.nodrag.nodrop td.linecoldescription');
			$('#tablelines tr.drag.drop').each(function(i){
				var html = $(this).next().find('.commandedet_extras_rhumerie').html();
				var td = $('<td width="150"></td>');
				td.append(html);
				td.insertAfter($(this).children()[0]);
				$(this).next().hide();
			});
			// réalignement des lignes d'ajout de produit et ligne dates
			addtd = $('<td class="nobottom">&nbsp</td>');
			addtd.insertAfter($('tr.liste_titre_add').children()[0]);
			addtd.clone().insertAfter($('#trlinefordates').children()[0]);
		});
		</script>
						
		<?php
	}
	
	/**
	 * Initialise le formulaire create ou init,
	 * mise en place du div d'injection
	 * et cache le champ de l'extrafield rhumerie
	 */
	function formInit(){
	
		print '<div id="selrhum" style="display: inline-block;margin-left: 10px;"></div>';
		
		?>
			<script type="text/javascript">
			
			$(document).ready(function(){
				// fix du champ produit en cas d'erreur de formulaire
				if($('#idprod').val() !== ''){
					$('#idprod').val('');
					$('#s2id_idprod').find('.select2-chosen').html('');
				}
				// cache la ligne de l'extrafield rhumerie
				$('input[name="options_rhumerie"]').parent().parent().hide();
				console.log('formInit terminé');
			});
			</script>
		<?php
	}
	
	/**
	 * Fix le select produit en cas d'erreur lors de l'envoie du formulaire
	 * et ajoute un select rhumerie à chaque selection d'un produit
	 */
	function addCreateSelect(){
		?>
		<script type="text/javascript">
		
		$(document).ready(function(){
			// fix du champ produit en cas d'erreur de formulaire
			if($('#idprod').val() !== ''){
				$('#idprod').val('');
				$('#s2id_idprod').find('.select2-chosen').html('');
			}
			
			// ajout du champ de selection de rhumerie au changement de produit
			$('#idprod').on('change', function(){
				$('#selrhum').prev().show();
				$('input[name="options_rhumerie"]').val('');
				$.get("<?php echo dol_buildpath('/rhum/card.php?action=ajaxselect&idprod=',1); ?>"+$(this).val(), function(data) {
					if($('#idprod').val() != '0'){
						var html = $(data);
					} else {
						var html = '';
					}
					
					$('#selrhum').html(html);
					
					$('select.rhumerie').on('change', function(){
						
						// Le champ de l'extrafield prend la valeur du select => l'id de la rhummerie
						var rhumerie = $(this).val();
						$('input[name="options_rhumerie"]').val(rhumerie);
					})
				});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * activation ou non de la création de ligne libre selon les droits
	 */
	function verifLignesLibres(){
		global $user;
		if(empty($user->rights->commande->lignelibre)){
			?>
			<script type="text/javascript">
				$(document).ready(function(){	
					$('#prod_entry_mode_free').attr('disabled', '');
					$('#select_type').attr('disabled', '').parent().append('<span>&nbsp;Vous n\'êtes pas autorisé à créer des lignes libres</span>');
					$('#prod_entry_mode_predef').click();
				});
			</script>
			<?php
		}
	
	}
	
	/**
	 * Ajoute le select rhumerie à la ligne à modifier si ce n'est pas une ligne libre
	 */
	function formEdit(){
		?>
			<script type="text/javascript">
			$(document).ready(function(){
				
				// si on est dans un produit libre on injecte pas le select
				if($('#line_'+<?php print GETPOST('lineid');?>).parent().find('a').length == 0){ 
					$('#selrhum').prev().remove();
				} else {
					// récupère l'id du produit de la ligne pour préparer la requête ajax
					line = $('#line_'+<?php print GETPOST('lineid');?>).parent().find('a').attr('href');
					idprod = line.substring(line.lastIndexOf('=')+1)
	
					// requête ajax qui récupère le selectarray des rhumerie dans lesquelles le produit est dispo
					$.get("<?php echo dol_buildpath('/rhum/card.php?action=ajaxselect&idprod=',1); ?>"+idprod, function(data) {
						var html = $(data);
						// chargement du select
						$('#selrhum').html(html);
	
						$('select.rhumerie').on('change', function(){
							// Le champ de l'extrafield prend la valeur du select => l'id de la rhummerie
							var rhumerie = $(this).val();
							$('input[name="options_rhumerie"]').val(rhumerie);
						})
	
						if($('input[name="options_rhumerie"]').val() != ''){
							// mise a jour du select au chargement
							var id = $('input[name="options_rhumerie"]').val();
							$('select.rhumerie').find('option[value=\"'+id+'\"]').attr('selected', '');
							$('#selrhum').find('.select2-chosen').html($('select.rhumerie').find('option[value='+id+']').html());
	
						}
					});
				}	
									
			});
			</script>
		
			<?php
	}
	
	/**
	 * Attrape les erreurs avant l'envoi au serveur
	 * (au cas où elles sont aussi gérées côté serveur)
	 */
	function catchErrors(){
		?>
			<script type="text/javascript">
			$(document).ready(function(){
				
				// interception en cas de rhumerie non-selectionnée lors de la création
				$('#addline').on('click', function(e){
					if($('input[name=prod_entry_mode]:checked').val() == 'predef'){
						if($('#idprod').val() == '' || $('#idprod').val() == '-1' ||  $('#idprod').val() == '0'){ // si on a rien sélectionné
							e.preventDefault();
						} else { // si on a sélectionné un produit
							if(($('input[name="options_rhumerie"]').val() == '-1' || $('input[name="options_rhumerie"]').val() == '') && $('select.rhumerie').length != 0){ // mais pas de rhumerie
								e.preventDefault(); e.stopPropagation();
																	
								$.get("<?php echo dol_buildpath('/rhum/card.php?action=ajaxerrors&errors=noselect',1); ?>", function(data) {
									$('#selrhum').append(data);
									$('[role=dialog]').last().find('.ui-button').last().html('<span class="ui-button-text">OK</span>').prev().remove();
								})
							} else if ($('select.rhumerie').length == 0){ // ou il n'y a pas de rhumerie à sélectionner
								e.preventDefault(); e.stopPropagation();
								$.get("<?php echo dol_buildpath('/rhum/card.php?action=ajaxerrors&errors=norhumerie',1); ?>", function(data) {
									$('#selrhum').append(data);
								})
							}	
						}
					}
				});

				// interception d'erreur à la modification
				$('#savelinebutton').on('click', function(e){
					if($('select.rhumerie').val() == '-1' && $('select.rhumerie').length > 0){
						e.preventDefault(); e.stopPropagation();
						$.get("<?php echo dol_buildpath('/rhum/card.php?action=ajaxerrors&errors=edit',1); ?>", function(data) {
							$('#selrhum').append(data);
							$('[role=dialog]').last().find('.ui-button').last().html('<span class="ui-button-text">OK</span>').prev().remove();
						})
					}
				});
				
			});
			
			</script>
			
			<?php
	}
}