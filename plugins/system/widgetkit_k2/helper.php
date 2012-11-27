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
        
private static function getWidget($productId, $type, $onlyRetrieve = false, $params = array()) {
                $name = 'wkvm_auto_'.$productId;
                $db = JFactory::getDbo();
                
                $db->setQuery('SELECT id, name, content FROM #__widgetkit_widget WHERE name = '.$db->quote($name));
                
                $rec = $db->loadObject();
                
                if ($onlyRetrieve) return $rec;
                
                if ($rec) {
                        $rec->content = json_decode($rec->content);
                        $rec->settings = $rec->content->settings;
                } else {
                        if ($params instanceof JRegistry) $params = $params->toArray();
                        $keys = array_keys($params);
                        $k = array_search('widget_style', $keys);
                        $settings = $k !== false ? array_slice($params, $k + 1) : array();
                        $rec = new stdClass();
                        $rec->id = '';
                        $rec->content = '';
                        $rec->settings = $settings;
                        $rec->name = $name;
                }
                
                return $rec;
        } 
        
        private static function save($item, $params) {
                $type = $params->get('widget_type', 'gallery');
                $widget = self::getWidget($item->id, $type, false, $params);
                
                if (!empty($widget) && !empty($widget->id)) return $widget->id;
                
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                
                $style = $params->get('widget_style', 'default');
                $images = $item->images;
                
                if (empty($images)) {
                        if (!empty($widget) && !empty($widget->id)) $wh->delete($widget->id);
                        
                        return true;
                }
                
                $captionPart = $params->get('caption_part', '');
                
                if ($type == 'gallery') {
                        $paths = array();
                        $captions = array();
                        $links = array();

                        foreach ($images as $image) {
                                $file = preg_replace('/^(\/|)images/', '', $image->file_url);
                                $path = preg_replace('/^(\/|)images/', '', $image->file_url_folder);
                                if (!in_array($path, $paths)) $paths[] = $path;
                                $captions[$file] = $captionPart ? $image->$captionPart : '';
                                $links[$file] = '';
                        }
                        
                        $data = array(
                                'type' => $type, 
                                'id' => $widget->id,
                                'name' => $widget->name, 
                                'settings' => $widget->settings,
                                'style' => $style,
                                'captions' => $captions,
                                'links' => $links,
                                'paths' => $paths
                        );                        
                } else if ($type == 'slideshow') {
                        $items = array();
                        $titlePart = $params->get('title_part');
                        $contentPart = $params->get('content_part', '');
                        $navigationPart = $params->get('navigation_part', '');
                        $navigationPart = explode('+', $navigationPart);
                        $contentPartPosition = $params->get('content_part_position', '');
                        $url = JURI::base();
                        
                        foreach ($images as $image) {
                                $id = uniqid();
                                $title = $titlePart ? $image->$titlePart : '';
                                $caption = $captionPart ? $image->$captionPart : '';
                                $alt = $image->file_meta;
                                
                                if (!$alt) $alt = $caption ? $caption : $title;
                                
                                $content = JHtml::image($url.$image->file_url, $alt);
                                
                                if ($contentPart) {
                                        if ($contentPartPosition == 'before') {
                                                $content = $image->$contentPart . $content;
                                        } else {
                                                $content = $content . $image->$contentPart;
                                        }
                                }
                                
                                $items[$id] = array('title'=>$title, 'content'=>$content, 'caption'=>$caption);
                                
                                if (!empty($navigationPart)) {
                                        $navigation = '';
                                        
                                        if (in_array('file_url_thumb', $navigationPart)) {
                                                $navigation .= JHtml::image($url.$image->file_url_thumb, $alt);
                                        }
                                        
                                        if (in_array('file_title', $navigationPart)) {
                                                $navigation .= $image->file_title;
                                        }
                                        
                                        if (in_array('file_description', $navigationPart)) {
                                                $navigation .= $image->file_description;
                                        }
                                        
                                        if ($navigation) {
                                                $items[$id]['navigation'] = $navigation;
                                        }
                                }
                                
                                if (!empty($navigation)) {
                                        $widget->settings['items_per_set'] = $params->get('items_per_set', 3);
                                        $widget->settings['slideset_buttons'] = 1;
                                }
                        }
                        
                        $data = array(
                                'type' => $type, 
                                'id' => $widget->id,
                                'name' => $widget->name, 
                                'settings' => $widget->settings,
                                'style' => $style,
                                'items' => $items
                        );                        
                }
                
                $data['settings']['style'] = $style;
                $source = $params->get('thumb_size_source', 'custom');
                
                if ($source == 'custom') {
                        $data['settings']['thumb_width'] = $params->get('thumb_width', 100);
                        $data['settings']['thumb_height'] = $params->get('thumb_height', 100);
                } else if ($source == 'vm') {
                        if (!class_exists('VmConfig')) {
                                require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
                        }
                        
                        VmConfig::loadConfig();
                        
                        $data['settings']['thumb_width'] = VmConfig::get('img_width', $params->get('thumb_width', 100));
                        $data['settings']['thumb_height'] = VmConfig::get('img_height', $params->get('thumb_height', 100));
                }
                
                return $wh->save($data);
        }
        
        public static function delete($productId = null) {
                if (!self::isInstalled()) return false;
                
                if ($productId) {
                        $widget = self::getWidget($productId, null, true);

                        if (!$widget) return;

                        $widgetkit = Widgetkit::getInstance();
                        $wh = $widgetkit->getHelper('widget');

                        return $wh->delete($widget->id);
                } else {
                        $db = JFactory::getDbo();
                        $db->setQuery('DELETE FROM #__widgetkit_widget WHERE name LIKE "wkvm_auto_%"');
                        $db->query();
                }
        }        
        
        public static function render($item, $params) {
                if (!self::isInstalled()) return '';
                
                $widgetId = self::save($item, $params);
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                
                $out = $wh->render($widgetId);
                
                return $out;
        }
        
        private static function isInstalled() {
                jimport('joomla.filesystem.file');
                
                if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/classes/widgetkit.php')
				|| !JComponentHelper::getComponent('com_widgetkit', true)->enabled) {
                        trigger_error('<b>Widgetkit K2 plugin</b>: Widgetkit is not installed.');
                        return;
                }
                
                require_once JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php';
                
                return true;
        }        
}
