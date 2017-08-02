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
			
			echo '<tr><td>Nombre de brasseries gérées</td><td>'.$obj->total.'</td></tr>';
		}
		
		
		if (in_array('productcard', explode(':', $parameters['context'])))
		{
			
		}
		
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			
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
				$('<td width="110">Rhumerie</td>').insertAfter('tr.liste_titre.nodrag.nodrop td.linecoldescription');
				
				// remplace l'id_rhumerie par le nom dans le tableau
				$('.commandedet_extras_rhumerie').each(function(){
					var name = $('#rhum').find('option[value="'+$(this).text()+'"]').html();
					$(this).html(name);
				});

				$('#tablelines tr').each(function(i){
					if(i > 0){
						$(this).next().find('.commandedet_extras_rhumerie').insertAfter($(this).children()[0]).removeAttribute('colspan');
						$(this).next().hide();
					}
				});
			});
			</script>
			
			
			<?php
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
			
			print '<div id="selrhum" style="display: inline-block;margin-left: 10px;"></div>';
			
			?>
			<script type="text/javascript">
			
			$(document).ready(function(){
				// cache la ligne de l'extrafield
				$('input[name="options_rhumerie"]').parent().parent().hide();
				
				$('#idprod').on('change', function(){

					$.get("<?php echo dol_buildpath('/rhum/card.php?action=ajaxselect&idprod=',1); ?>"+$(this).val(), function(data) {
						var html = $(data);
						$('#selrhum').html(html);

						$('select.rhumerie').on('change', function(){

							// Le champ de l'extrafield prend la valeur du select => l'id de la rhummerie
							var rhumerie = $(this).val();
							$('input[name="options_rhumerie"]').val(rhumerie);
						})

						// interception en cas de rhumerie non-selectionnée
						$('#addline').on('click', function(e){
							if($('#idprod').val() != ''){
								if($('select.rhumerie').val() == '-1'){
									e.preventDefault(); e.stopPropagation();
									alert('Vous n\'avez pas sélectionné de rhumerie !');
								} else if ($('select.rhumerie').length == 0){
									e.preventDefault(); e.stopPropagation();
									alert('Ce produit ne peut être commandé en l\'état ! il faut l\'assigner à une rhumerie...');
								}	
							}
						});
					});
				});
			});
			
			</script>
			
			<?php
			
		}
	}
	
	
	function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			print '<label>Rhumerie : </label><div id="selrhum" style="display: inline-block;margin-left: 10px;"></div>';
				
			?>
			<script type="text/javascript">
			$(document).ready(function(){
				// cache la ligne de l'extrafield
				$('input[name="options_rhumerie"]').parent().parent().hide();

				line = $('#line_'+<?php print GETPOST('lineid');?>).parent().find('a').attr('href');
				idprod = line.substring(line.lastIndexOf('=')+1)

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

					$('#savelinebutton').on('click', function(e){
						if($('select.rhumerie').val() == '-1'){
							e.preventDefault(); e.stopPropagation();
							alert('Vous n\'avez pas sélectionné de rhumerie !');
						}
					});
				});
									
			});
			</script>
			
			<?php
		}
	}

}