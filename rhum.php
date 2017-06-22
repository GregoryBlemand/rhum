<?php

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
	if($fk_rhumerie == null) $fk_rhumerie = $object->fk_rhumerie;
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
				,'showRef' => ($action == 'create') ? $langs->trans('Draft') : $formcore->texte('', 'ref', $object->ref, 80, 255)
				,'showLabel' => $formcore->texte('', 'label', $object->label, 80, 255)
				,'showFk_rhumerie' => $rhumerie->getNomUrl(1) . '<input type="hidden" name="fk_rhumerie" value='.$fk_rhumerie.'>'
				,'showPrix' => $formcore->texte('', 'prix', $object->prix, 80, 255)
			)
			,'langs' => $langs
			,'user' => $user
			,'conf' => $conf
			
		)
	);
	
	if ($mode == 'edit') echo $formcore->end_form();
	
	llxFooter();

}