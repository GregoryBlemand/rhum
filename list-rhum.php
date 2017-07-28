<?php

require 'config.php';
dol_include_once('/rhum/class/rhum.class.php');
dol_include_once('/rhum/lib/rhum.lib.php');

//je vire ce accessforbidden() parce que un peu moche... quand on te parle en anglais c'est mauvais signe
//if(empty($user->rights->rhum->read)) accessforbidden();

// vérifie les droits en lecture
if(empty($user->rights->rhum->read)){
	setEventMessages('Vous n\'avez pas les droits pour accéder au module rhumerie !', null, 'errors');
	header('Location: '.dol_buildpath('/', 1));
	exit;
}

$langs->load('abricot@abricot');
$langs->load('rhum@rhum');

$fk_rhumerie = GETPOST('fk_rhumerie');

$PDOdb = new TPDOdb;
$object = new TRhumerie;

if(!$object->load($PDOdb, $fk_rhumerie)) {
	exit('$fk_rhumerie ?');
}



$hookmanager->initHooks(array('rhumlist'));

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// do action from GETPOST ... 
}


/*
 * View
 */

llxHeader('',$langs->trans('RhumsList'),'','');
$head = rhum_prepare_head($object);
$picto = 'generic';
dol_fiche_head($head, 'rhum', $langs->trans("Rhums"), 0, $picto);
//$type = GETPOST('type');
//if (empty($user->rights->rhum->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
$sql = 'SELECT t.rowid, t.ref, t.label, t.date_cre, t.date_maj, t.prix, t.fk_rhumerie';

$sql.= ' FROM '.MAIN_DB_PREFIX.'rhum t ';

$sql.= ' WHERE 1=1';

$sql.= ' AND fk_rhumerie = '.$fk_rhumerie;
//$sql.= ' AND t.entity IN ('.getEntity('Rhum', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;


$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_rhum', 'GET');

$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

$r = new TListviewTBS('rhum');
echo $r->render($PDOdb, $sql, array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'limit'=>array(
		'nbLine' => $nbLine
	)
	,'subQuery' => array()
	,'link' => array(
			'label'=>'<a href="rhum.php?id=@rowid@">@val@</a>'
	)
	,'type' => array(
		'date_cre' => 'date' // [datetime], [hour], [money], [number], [integer]
		,'date_maj' => 'date'
	)
	,'search' => array(
		'date_cre' => array('recherche' => 'calendars', 'allow_is_null' => true)
		,'date_maj' => array('recherche' => 'calendars', 'allow_is_null' => false)
		,'label' => array('recherche' => true, 'table' => array('t', 't'), 'field' => array('label', 'description')) // input text de recherche sur plusieurs champs
		,'prix' => array('recherche' => true, 'table' => array('t', 't'), 'field' => array('prix'))
	)
	,'translate' => array()
	,'hide' => array(
		'rowid'
		,'fk_rhumerie'
	)
	,'liste' => array(
		'titre' => $langs->trans('RhumsList')
		,'image' => img_picto('','title_generic.png', '', 0)
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('NoRhum')
		,'picto_search' => img_picto('','search.png', '', 0)
	)
	,'title'=>array(
		'ref' => $langs->trans('Ref.')
		,'label' => $langs->trans('Label')
		,'date_cre' => $langs->trans('DateCre')
		,'date_maj' => $langs->trans('DateMaj')
	)
	,'eval'=>array(
//		'fk_user' => '_getUserNomUrl(@val@)' // Si on a un fk_user dans notre requête
	)
));

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print "<div class=\"tabsAction\"><a href=\"rhum.php?mode=edit&action=create&fk_rhumerie=".$object->getId()."\" class=\"butAction\"> Nouveau Rhum </a></div>";
$formcore->end_form();
dol_fiche_end();
llxFooter('');

/**
 * TODO remove if unused
 */
function _getUserNomUrl($fk_user)
{
	global $db;
	
	$u = new User($db);
	if ($u->fetch($fk_user) > 0)
	{
		return $u->getNomUrl(1);
	}
	
	return '';
}