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
			?>
			<script type="text/javascript">
				// ajoute la "nature" rhum au select nature pour pouvoir jouer avec durant la commande
				$(document).ready(function(){
					if($('#finished').lenght > 0){
						$('#finished').append('<option value=\"2\">Rhum</option>');
					}
				});
			</script>
			
			
			
			<?php 
		}
		
		if (in_array('ordercard', explode(':', $parameters['context'])))
		{
			global $db;
			
			// récupérer la liste des rhumeries à afficher
			$sql = 'SELECT t.rowid, t.label';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'rhumerie t ';
			$sql.= ' WHERE 1';
			
			$res = $db->query($sql);
			
			if($res){
				$select = '<select name="rhumerie" id="rhumerie">';
				$select .= '<option value="0" selected></option>';
				
				while($obj = $db->fetch_object($res)){
					$select .= '<option value="' . $obj->rowid . '">' . $obj->label . '</option>';
				}
		
				$select .= '</select>';
			}
			
			?>
			<script type="text/javascript">
			$(document).ready(function(){
				$('#idprod').on('change', function(){
					console.log($(this).val());
					if($('#rhumerie').length == 0){
						$('span.prod_entry_mode_predef').append('<label for="rhumerie"> Rhumerie : </label><?php print $select ?>');
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
}