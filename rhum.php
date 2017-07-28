<?php

// Ceci est la card qui sert pour les rhums dans les rhumeries...

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/rhum/class/rhum.class.php');
dol_include_once('/rhum/lib/rhum.lib.php');

if(empty($user->rights->rhum->read)) accessforbidden();

$langs->load('rhum@rhum');

$action = GETPOST('action');
$socid = GETPOST('socid', 'int');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_rhumerie = GETPOST('fk_rhumerie');

$mode = 'view';
if (empty($user->rights->rhum->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$PDOdb = new TPDOdb;
$object = new TRhum;

if (!empty($id)) $object->load($PDOdb, $id);
elseif (!empty($ref)) $object->loadBy($PDOdb, $ref, 'ref');

$hookmanager->initHooks(array('rhumcard', 'globalcard'));

// vérifie les droits en lecture
if(!$user->rights->rhum->read){
	setEventMessages('Vous n\'avez pas les droits pour accéder au module rhumerie !', null, 'errors');
	header('Location: '.dol_buildpath('/', 1));
	exit;
}

// vérifie les droits en édition
$modifArray = ['edit', 'save', 'confirm_clone','modif', 'confirm_validate'];

if(in_array($action, $modifArray) && empty($user->rights->rhum->write)){
	setEventMessages('Vous n\'avez pas les droits de création/modification !', null, 'errors');
	header('Location: '.dol_buildpath('/rhum/card.php', 1).'?id='.$object->getId());
	exit;
}

// vérifie les droits de suppression
if($action == 'confirm_delete' && empty($user->rights->rhum->delete)){
	setEventMessages('Vous n\'avez pas les droits de suppression !', null, 'errors');
	header('Location: '.dol_buildpath('/', 1));
	exit;
}

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	$error = 0;
	switch ($action) {
		case 'save':
			if($user->rights->rhum->write){ // si l'utilisateur à les droits en écriture
				$object->set_values($_REQUEST); // Set standard attributes
				
	//			$object->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));
	
				// Check parameters
	//			if (empty($object->date_other))
	//			{
	//				$error++;
	//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
	//			}
				
				// ... 
				
				if ($error > 0)
				{
					$mode = 'edit';
					break;
				}
				
				$object->save($PDOdb, empty($object->ref));
				
				header('Location: '.dol_buildpath('/rhum/rhum.php', 1).'?id='.$object->getId());
				exit;
			} else { // sinon je t'affiche la liste
				setEventMessages('Vous n\'avez pas les droits de création !', null, 'errors');
				header('Location: '.dol_buildpath('/rhum/list-rhum.php', 1));
				exit;
			}
			
			break;
		case 'confirm_clone':
			$object->cloneObject($PDOdb);
			
			header('Location: '.dol_buildpath('/rhum/rhum.php', 1).'?id='.$object->getId());
			exit;
			break;
		case 'modif':
			if (!empty($user->rights->rhum->write)) $object->setDraft($PDOdb);
			header('Location: '.dol_buildpath('/rhum/rhum.php', 1).'?id='.$object->getId());
			break;
		case 'confirm_validate':
			if (!empty($user->rights->rhum->write)) $object->setValid($PDOdb);
			
			header('Location: '.dol_buildpath('/rhum/rhum.php', 1).'?id='.$object->getId());
			exit;
			break;
		case 'confirm_delete':
			$fk_rhumerie = $object->fk_rhumerie;
			if (!empty($user->rights->rhum->write)) $object->delete($PDOdb);
			header('Location: '.dol_buildpath('/rhum/list-rhum.php', 1).'?fk_rhumerie='.$fk_rhumerie);
			exit;
			break;
		// link from llx_element_element
		case 'dellink':
			$object->generic->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/rhum/card.php', 1).'?id='.$object->getId());
			exit;
			break;
		default:
			
			_card($PDOdb, $object, $action, $mode);
	}
}



function _card(&$PDOdb, &$object, $action, $mode) {

	global $conf, $db, $user, $langs, $id, $socid;
	
	/**
	 * View
	 */
	
	$fk_rhumerie = GETPOST('fk_rhumerie');
	if(empty($fk_rhumerie)) $fk_rhumerie = $object->fk_rhumerie;
	$rhumerie = new TRhumerie;
	$rhumerie->load($PDOdb, $fk_rhumerie);
	
	$title=$langs->trans("Rhumerie");
	llxHeader('',$title);
	$head = rhum_prepare_head($rhumerie);
	$picto = 'generic';
	dol_fiche_head($head, 'rhum', $langs->trans("Rhums"), 0, $picto);
	
	$formcore = new TFormCore;
	$formcore->Set_typeaff($mode);
	
	$form = new Form($db);
	
	$formconfirm = getFormConfirm($PDOdb, $form, $object, $action);
	if (!empty($formconfirm)) echo $formconfirm;
	
	$TBS=new TTemplateTBS();
	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_rhum');
	
	$linkback = '<a href="'.dol_buildpath('/rhum/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
	print $TBS->render('tpl/card-rhum.tpl.php'
		,array() // Block
		,array(
			'object'=>$object
			,'view' => array(
				'mode' => $mode
				,'action' => 'save'
				,'urlcard' => dol_buildpath('/rhum/card.php', 1)
				,'urllist' => dol_buildpath('/rhum/list.php', 1)
				,'showRef' => ($action == 'create') ? $formcore->texte('', 'ref', $object->label, 80, 255): $object->ref
				,'showLabel' => $formcore->texte('', 'label', $object->label, 80, 255, 'required="required"')
				,'showFk_rhumerie' => $rhumerie->getNomUrl(1) . '<input type="hidden" name="fk_rhumerie" value='.$fk_rhumerie.'>'
				,'showPrix' => $formcore->texte('', 'prix', $object->prix, 80, 255, 'required="required"')
			)
			,'langs' => $langs
			,'user' => $user
			,'conf' => $conf
			
		)
	);
	
	if ($mode == 'edit') echo $formcore->end_form();
	
	dol_fiche_end();
	llxFooter();

}