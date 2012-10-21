<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class WidgetkitK2WidgetkitHelper extends WidgetkitHelper {
        private static function getItemLayout($item, $ext, $extType, $layoutDir, $extLayoutDir, $default) {
                $extDir = '/' . $extType . 's/' . $ext;
                $tmpl = JFactory::getApplication()->getTemplate();

                $dirs = array(
                    JPATH_SITE . '/templates/' . $tmpl . '/html/' . $layoutDir . '/',
                    JPATH_SITE . $extDir . '/' . $extLayoutDir . '/'
                );

                $tmpl = '';

                // In priority order
                $files = array(
                    'i' . $item->id . '.php',
                    'c' . $item->catid . '.php',
                    'item.php'
                );

                foreach ($dirs as $dir) {
                        foreach ($files as $file) {
                                if (JFile::exists($dir . $file)) {
                                        $tmpl = $dir . $file;
                                        break;
                                }
                        }

                        if (!empty($tmpl))
                                break;
                }

                return $tmpl;
        }        
        
	public function renderItem($item, $params) {
                $tmpl = self::getItemLayout(
                                $item, 'system/widgetkit_k2', 'plugin', 'plg_widgetkit_k2', 'layouts', dirname(__FILE__) . '/layouts/item.php'
                );
                
                // Copied from modules/mod_k2_content/mod_k2_content.php
                $itemAuthorAvatarWidthSelect = $params->get('itemAuthorAvatarWidthSelect','custom');
                $itemAuthorAvatarWidth = $params->get('itemAuthorAvatarWidth', 50);
                $itemCustomLinkTitle = $params->get('itemCustomLinkTitle', '');
                if ($params->get('itemCustomLinkMenuItem')) {
                        $menu = &JMenu::getInstance('site');
                        $menuLink = $menu->getItem($params->get('itemCustomLinkMenuItem'));
                        if(!$itemCustomLinkTitle){
                                $itemCustomLinkTitle = (K2_JVERSION == '16') ? $menuLink->title : $menuLink->name;
                        }
                        $params->set('itemCustomLinkURL', JRoute::_($menuLink->link.'&Itemid='.$menuLink->id));
                }

                // Get component params
                $componentParams = & JComponentHelper::getParams('com_k2');

                // User avatar
                if($itemAuthorAvatarWidthSelect=='inherit'){
                        $avatarWidth = $componentParams->get('userImageWidth');
                } else {
                        $avatarWidth = $itemAuthorAvatarWidth;
                }
                
                if (!isset($item->event->BeforeDisplay)) $item->event->BeforeDisplay = '';
                if (!isset($item->event->AfterDisplayTitle)) $item->event->AfterDisplayTitle = '';
                if (!isset($item->event->BeforeDisplayContent)) $item->event->BeforeDisplayContent = '';
                if (!isset($item->event->AfterDisplayContent)) $item->event->AfterDisplayContent = '';
                if (!isset($item->event->AfterDisplay)) $item->event->AfterDisplay = '';
                
                JLoader::register('K2HelperUtilities', JPATH_SITE.'/components/com_k2/helpers/utilities.php');                

                ob_start();
                require $tmpl;
                $result = ob_get_contents();
                ob_end_clean();
                
                return $result;
	}
        
        public function getList($params) {
                if ($params->get('based_on', 'mod_k2_content') == 'mod_k2fields_contents') {
                        $componentParams = JComponentHelper::getParams('com_k2');
                        JLoader::register('K2FieldsModuleHelper', JPATH_SITE.'/components/com_k2fields/helpers/modulehelper.php');
                        $items = K2FieldsModuleHelper::getList($params, $componentParams, 'html', null, 'widgetkit_k2');
                } else {
                        JLoader::register('modK2ContentHelper', JPATH_SITE.'/modules/mod_k2_content/helper.php');
                        JLoader::register('K2ModelItemlist', JPATH_SITE.'/components/com_k2/models/itemlist.php');
                        $items = modK2ContentHelper::getItems($params);
                }
                
                return $items;
        }
}
