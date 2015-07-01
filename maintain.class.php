<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class photo_quick_validation_maintain extends PluginMaintain
{
  private $installed = false;

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);
  }

  function install($plugin_version, &$errors=array())
  {
    global $conf, $prefixeTable;

    $result = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "pqv_validated";');
    if (!pwg_db_num_rows($result))
    {
      pwg_query('ALTER TABLE '.IMAGES_TABLE.' ADD pqv_validated enum(\'true\', \'false\') DEFAULT NULL;');
    }

    $result = pwg_query('SHOW COLUMNS FROM `'.GROUPS_TABLE.'` LIKE "pqv_enabled";');
    if (!pwg_db_num_rows($result))
    {
      pwg_query('ALTER TABLE '.GROUPS_TABLE.' ADD pqv_enabled enum(\'true\', \'false\') DEFAULT \'false\';');
    }
    
    $this->installed = true;
  }

  function activate($plugin_version, &$errors=array())
  {
    global $prefixeTable;
    
    if (!$this->installed)
    {
      $this->install($plugin_version, $errors);
    }
  }

  function update($old_version, $new_version, &$errors=array())
  {
    $this->install($new_version, $errors);
  }
  
  function deactivate()
  {
  }

  function uninstall()
  {
    global $prefixeTable;
  
    $query = 'ALTER TABLE '.IMAGES_TABLE.' DROP pqv_validated;';
    pwg_query($query);
    
    $query = 'DROP TABLE '.GROUPS_TABLE.' DROP pqv_enabled;';
    pwg_query($query);
  }
}
?>
