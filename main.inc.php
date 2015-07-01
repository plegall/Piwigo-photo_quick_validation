<?php
/*
Plugin Name: Photo Quick Validation
Version: auto
Description: quickly mark photos as validated or rejected
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=
Author: plg
Author URI: http://le-gall.net/pierrick
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

global $prefixeTable;

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

defined('PQV_ID') or define('PQV_ID', basename(dirname(__FILE__)));
define('PQV_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');

// include_once(PQV_PATH.'include/functions.inc.php');

// init the plugin
add_event_handler('init', 'pqv_init');
/**
 * plugin initialization
 *   - check for upgrades
 *   - unserialize configuration
 *   - load language
 */
function pqv_init()
{
  global $conf;

  // prepare plugin configuration
  // $conf['pqv'] = safe_unserialize($conf['pqv']);
}

add_event_handler('get_admin_plugin_menu_links', 'pqv_admin_menu');
function pqv_admin_menu($menu)
{
  global $page;

  array_push(
    $menu,
    array(
      'NAME' => 'Photo Quick Validation',
      'URL'  => get_root_url().'admin.php?page=plugin-photo_quick_validation'
      )
    );

  return $menu;
}

add_event_handler('ws_add_methods', 'pqv_add_methods');
function pqv_add_methods($arr)
{
  $service = &$arr[0];

  $service->addMethod(
    'pwg.pqv.update',
    'ws_pqv_update',
    array(
      'image_id' => array('default'=>null,'type'=>WS_TYPE_ID),
      'action' => array('default'=>null,'info'=>'up, down'),
      ),
    'Validate or reject a photo'
    );
}

function ws_pqv_update($params, &$service)
{
  if (!pqv_is_active())
  {
    return new PwgError(401, 'Access denied');
  }
  
  $query = '
SELECT pqv_validated
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';
  list($pqv_validated) = pwg_db_fetch_row(pwg_query($query));

  $pqv_validated_new = 'null';

  if ($params['action'] == 'up') {
    $pqv_validated_new = ($pqv_validated == 'false' ? 'null' : 'true');
  }

  if ($params['action'] == 'down') {
    $pqv_validated_new = ($pqv_validated == 'true' ? 'null' : 'false');
  }

  $query = '
UPDATE '.IMAGES_TABLE.'
  SET pqv_validated = '.($pqv_validated_new == 'null' ? $pqv_validated_new : "'".$pqv_validated_new."'").'
  WHERE id = '.$params['image_id'].'
;';
  pwg_query($query);

  return array('pqv_validated' => $pqv_validated_new);
}

add_event_handler('loc_end_section_init', 'pqv_end_section_init');
function pqv_end_section_init()
{
  global $template, $page;

  if (!pqv_is_active())
  {
    return;
  }

  $template->set_filename('front_js', realpath(PQV_PATH.'front_js.tpl'));
  $template->parse('front_js');


  if (empty($page['items']))
  {
    return;
  }
  
  $query = '
SELECT
    id
  FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',', $page['items']).')
    AND pqv_validated = \'false\'
;';
  $pqv_rejected = query2array($query, null, 'id');

  if (isset($_GET['pqv_delete']) and count($pqv_rejected) > 0)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    
    $deleted_count = delete_elements($pqv_rejected, true);
    
    if ($deleted_count > 0)
    {
      invalidate_user_cache();
      
      $_SESSION['page_infos'][] = l10n_dec(
        '%d photo was deleted', '%d photos were deleted',
        $deleted_count
        );
      
      $redirect_url = duplicate_index_url(array(), array('pqv_delete'));
      
      redirect($redirect_url);
    }
  }

  if (count($pqv_rejected) > 0)
  {
    $delete_url = add_url_params(duplicate_index_url(), array('pqv_delete'=>1));
    
    $template->assign(
      'CONTENT_DESCRIPTION',
      '<a href="'.$delete_url.'" onclick="return confirm(\''.l10n('Are you sure?').'\');">delete the '.count($pqv_rejected).' rejected photo(s)</a>'
      );
  }
}

/**
 * check permissions for the current user
 */
function pqv_is_active()
{
  global $user;

  if (!isset($user['id']))
  {
    return false;
  }

  $query = '
SELECT
    COUNT(*)
  FROM '.GROUPS_TABLE.'
    JOIN '.USER_GROUP_TABLE.' ON group_id = id
  WHERE user_id = '.$user['id'].'
    AND pqv_enabled = \'true\'
;';
  list($counter) = pwg_db_fetch_row(pwg_query($query));

  if ($counter > 0)
  {
    return true;
  }

  return false;
}
?>
