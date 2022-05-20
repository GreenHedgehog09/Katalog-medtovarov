<?php
/**
* @package OS Gallery
* @copyright 2020 OrdaSoft
* @author 2020 Andrey Kvasnevskiy(akbet@mail.ru),Roman Akoev (akoevroman@gmail.com), Vladislav Prikhodko(vlados.vp1@gmail.com)
* @license GNU General Public license version 2 or later;
* @description Ordasoft Image Gallery
*/
// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Script file for the plg_system_example plugin    
 */
class plgSystemOsGallery_systemInstallerScript{
  /**
   * Method to run after the plugin install, update, or discover_update actions have completed.
   *
   * @return void
   */
  public function postflight($route, $adapter) {
    // Get a database connector object
        $db = JFactory::getDbo();
    
        try
        {
            // Enable plugin by default
            $q = $db->getQuery(true);
     
            $q->update('#__extensions');
            $q->set(array('enabled = 1', 'ordering = 9999'));
            $q->where("element = 'osgallery_system'");
            $q->where("type = 'plugin'", 'AND');
            $q->where("folder = 'system'", 'AND');
            $db->setQuery($q);
            method_exists($db, 'execute') ? $db->execute() : $db->query();
        }
        catch (Exception $e)
        {
           throw $e;
        }
  }
} 


