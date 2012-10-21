<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class WidgetkitK2WidgetkitHelper extends WidgetkitHelper {
//        public static function getFolders($node) {
//                // Initialize variables.
//                $options = array();
//
//                // Initialize some field attributes.
//                $filter = (string) $node->attributes()->filter;
//                $exclude = (string) $node->attributes()->exclude;
//                $hideNone = (string) $node->attributes()->hide_none;
//                $hideDefault = (string) $node->attributes()->hide_default;
//
//                // Get the path in which to search for file options.
//                $path = (string) $node->attributes()->directory;
//                if (!is_dir($path))
//                {
//                        $path = JPATH_ROOT . '/' . $path;
//                }
//
//                // Prepend some default options based on field attributes.
//                if (!$hideNone)
//                {
//                        $options[] = JHtml::_('select.option', '-1', JText::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $node->attributes()->fieldname)));
//                }
//                if (!$hideDefault)
//                {
//                        $options[] = JHtml::_('select.option', '', JText::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $node->attributes()->fieldname)));
//                }
//
//                // Get a list of folders in the search path with the given filter.
//                $folders = JFolder::folders($path, $filter);
//
//                // Build the options list from the list of folders.
//                if (is_array($folders))
//                {
//                        foreach ($folders as $folder)
//                        {
//
//                                // Check to see if the file is in the exclude mask.
//                                if ($exclude)
//                                {
//                                        if (preg_match(chr(1) . $exclude . chr(1), $folder))
//                                        {
//                                                continue;
//                                        }
//                                }
//
//                                $options[] = JHtml::_('select.option', $folder, $folder);
//                        }
//                }  
//                
//                return $options;
//        }
//        
//        public static function renderField($node, $value) {
//                $type = (string) $node->attributes()->type;
//                
//                if ($type == 'k2type') {
//                        $type = (string) $node->attributes()->k2type;
//                        $node->attributes()->type = $type;
//                        JFormHelper::addFieldPath(JPATH_ADMINISTRATOR.'/components/com_k2/elements/');
//                }
//                
//                if (!($type = JFormHelper::loadFieldClass($type))) {
//                        return 'Given element type:'.$type.' does not exist.';
//                }
//                
//                if (!($node instanceof JXMLElement)) $node = new JXMLElement($node->asXML());
//                
//                $el = new $type;
//                $html = '';
//                
//                if ($el->setup($node, $value, 'k2')) $html = $el->getInput();
//                else return 'Unable to initialize type: '.$type;
//                
//                return $html;
//        }
        
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
