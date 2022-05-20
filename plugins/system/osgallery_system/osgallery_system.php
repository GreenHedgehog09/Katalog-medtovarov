<?php
/**
* @package OS Gallery
* @copyright 2020 OrdaSoft
* @author 2020 Andrey Kvasnevskiy(akbet@mail.ru),Roman Akoev (akoevroman@gmail.com), Vladislav Prikhodko(vlados.vp1@gmail.com)
* @license GNU General Public license version 2 or later;
* @description Ordasoft Image Gallery
*/


defined('_JEXEC') or die;

jimport( 'joomla.plugin.plugin' );

class plgSystemOsGallery_system extends JPlugin{
/**
* Constructor.
* @access protected
* @param object $subject The object to observe
* @param array   $config  An array that holds the plugin configuration
* @since 1.0
*/

  public function __construct( &$subject, $config ){
    parent::__construct( $subject, $config );
  }

  public function onContentPrepare($context, &$article, &$params){
    $app = JFactory::getApplication();
    $doc = JFactory::getDocument();
    $html = $app->getBody();
    //if ($app->isClient('site') && $doc->getType() == 'html') { //for joomla 4
    if (version_compare(JVERSION, "4.0.0", "ge")) {
      if ($app->isClient('site') && $doc->getType() == 'html'){
        $check = true;
      }else{
        $check = false;
      }
    }else{
      if ($app->isSite() && $doc->getType() == 'html'){
        $check = true;
      }else{
        $check = false;
      }
    }
    if ($check) {
      JLoader::register('osGallerySocialButtonsHelper', JPATH_SITE . '/components/com_osgallery/helpers/osGallerySocialButtonsHelper.php');
      JLoader::register('osGalleryHelperSite', JPATH_SITE . "/components/com_osgallery/helpers/osGalleryHelperSite.php");
      $GLOBALS['gl_state'] = $gl_state = osGalleryHelperSite::checkActivation() ;
      $GLOBALS['os_gallery_configuration'] = $os_gallery_configuration = JComponentHelper::getParams('com_osgallery');
      
      if(isset($article->introtext)){
        $article_content = $article->introtext;
        preg_match_all('{os-gal-[0-9]{1,}}',$article_content,$matches);
        
        if(isset($matches[0]) && count($matches[0])){
          foreach ($matches[0] as $key => $shortCode) {
            if(strpos("os-gal-", $shortCode) == 0){
              $galId = str_replace('os-gal-', '', $shortCode);
              $galIds = array(0=>$galId);
              //other layout
              ob_start();
                osGalleryHelperSite::displayView($galIds);
                $article_content = str_replace("{os-gal-".$galId."}", ob_get_contents(), $article_content);
              ob_end_clean();
            }
          }
        }
        $article->introtext = $article_content;
      }
    }
  }

  public function onAfterRender()
  {
    $app = JFactory::getApplication();
    $doc = JFactory::getDocument();
    $input = $app->input;
    $edit = false;
    
    if($input->get('view', '') == 'form' && $input->get('layout', '') == 'edit'){
        $edit = true;
    }
    
    if(!$edit){
        
        $db = JFactory::getDBO();
        $params = new JRegistry;
        $html = $app->getBody();
        if (version_compare(JVERSION, "4.0.0", "ge")) {
          if ($app->isClient('site') && $doc->getType() == 'html'){
            $check = true;
          }else{
            $check = false;
          }
        }else{
          if ($app->isSite() && $doc->getType() == 'html'){
            $check = true;
          }else{
            $check = false;
          }
        }
        if ($check) {
            
        //if ($app->isClient('site') && $doc->getType() == 'html') { // for joomla 4
          $html = $app->getBody();
          $pos = strpos($html, '</head>');
          $head = substr($html, 0, $pos);
          
          $body = substr($html, $pos);
          JLoader::register('osGallerySocialButtonsHelper', JPATH_SITE . '/components/com_osgallery/helpers/osGallerySocialButtonsHelper.php');
          JLoader::register('osGalleryHelperSite', JPATH_SITE . "/components/com_osgallery/helpers/osGalleryHelperSite.php");
          $GLOBALS['gl_state'] = $gl_state = osGalleryHelperSite::checkActivation() ;
          $GLOBALS['os_gallery_configuration'] = $os_gallery_configuration = JComponentHelper::getParams('com_osgallery');
          if(isset($body)){
            preg_match_all('{os-gal-[0-9]{1,}(-[0-9]{1,})?(-[0-9]{1,}(,[0-9]{1,})*)?(-[a-z]{4,6})?}',$body,$matches,PREG_OFFSET_CAPTURE);
            //preg_match_all('{os-gal-[0-9]{1,}(-[0-9]{1,})?(-[0-9]{1,})?}',$body,$matches2,PREG_OFFSET_CAPTURE);
            if(isset($matches[0]) && count($matches[0])){
              $buttons = false;
              $thumbnail = false;
              $wheel = false;
              foreach ($matches[0] as $key => $shortCode) {
                
                if(strpos("os-gal-", $shortCode[0]) == 0){
                  $short_code_array = explode('-', $shortCode[0]);
                  
                  //$galId = str_replace('os-gal-', '', $shortCode[0]);
                  $galId = $short_code_array[2];
                  $catId = (isset($short_code_array[3])) ? $short_code_array[3] : null;
                  $imgId = (isset($short_code_array[4])) ? explode(',', $short_code_array[4]) : null;
                  $alignment = (isset($short_code_array[5])) ? $short_code_array[5] : null;
                  //load params
                  $query = "SELECT params FROM #__os_gallery WHERE id=$galId";
                  $db->setQuery($query);
                  $paramsString = $db->loadResult();
                  if($paramsString){
                      $params->loadString($paramsString);
                  }
                  if($params->get("helper_buttons"))$buttons = true;
                  if($params->get("helper_thumbnail"))$thumbnail = true;
                  if($params->get("mouse_wheel",1))$wheel = true;
                  $galIds = array(0=>$galId);

                  //Check for the EasyBlog component that inserts shortcods into the JavaScript
                  if($key == 0){
                    $substr_pos1 = $shortCode[1] - 1000;
                    $checked_segment = substr($body, $substr_pos1, 1050);
                  }else{
                    preg_match_all('{os-gal-[0-9]{1,}}',$body,$matches2,PREG_OFFSET_CAPTURE);
                    $substr_pos1 = $matches2[0][0][1] - 1000;
                    $checked_segment = substr($body, $substr_pos1, 1050);
                  }

                  if(stripos($checked_segment, '"articleBody":') === FALSE){
                    ob_start();
                      osGalleryHelperSite::displayView($galIds, $catId, $imgId, $alignment);
                      $body = preg_replace("#{".$shortCode[0]."}#", ob_get_contents(), $body, 1);
                    ob_end_clean();
                  }else{
                      $body = preg_replace("#{".$shortCode[0]."}#", '['.$shortCode[0].']', $body, 1);
                  }
                  //other layout

                }
              }
              $head = $this->addStyle($head, $buttons, $thumbnail, $wheel);
            }
          }
          $path = JURI::getInstance()->toString();
          $isSEF = JFactory::getConfig()->get('sef');
          $concat = ($isSEF) ? '?' : '&';
          $pathFragments = explode($concat, $path);
          $endFrag = end($pathFragments);
          if(stripos($endFrag, 'os_image_id') !== false){
              $head = $this->addMetaTags($head);
          }

          $app->setBody($head.$body);
        }
    }
  }

  public function addStyle($head, $buttons, $thumbnail, $wheel){
    $link = JURI::base() . 'components/com_osgallery/assets/css/os-gallery.css';
    if(!preg_match_all('|os-gallery.css|',$head,$matches)){
      $head .= '<link rel="stylesheet" href="'.$link.'">'."\n";
    }

    // $link = JURI::base() . 'components/com_osgallery/assets/css/font-awesome.min.css';
    // if(!preg_match_all('|font-awesome.min.css|',$head,$matches)){
    //   $head .= '<link rel="stylesheet" href="'.$link.'">'."\n";
    // }

    $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/jquer.os_fancybox.css';
    if(!preg_match_all('|jquer.os_fancybox.css|',$head,$matches)){
      $head .= '<link rel="stylesheet" href="'.$link.'">'."\n";
    }

    $link = JURI::base() . 'components/com_osgallery/assets/libraries/jQuer/jQuerOs-2.2.4.min.js';
    if(!preg_match_all('|jQuerOs-2.2.4.min.js|',$head,$matches)){
      $head .= '<script type="text/javascript" src="'.$link.'"></script>'."\n";
    }

    $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/jquer.os_fancybox.js';
    if(!preg_match_all('|jquer.os_fancybox.js|',$head,$matches)){
      $head .= '<script type="text/javascript" src="'.$link.'"></script>'."\n";
    }

    
    


    if($buttons){
      $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/helpers/jquer.os_fancybox-buttons.css';
      if(!preg_match_all('|jquer.os_fancybox-buttons.css|',$head,$matches)){
        $head .= '<link rel="stylesheet" href="'.$link.'">'."\n";
      }

      $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/helpers/jquer.os_fancyboxGall-buttons.js';
      if(!preg_match_all('|jquer.os_fancyboxGall-buttons.js|',$head,$matches)){
        $head .= '<script type="text/javascript" src="'.$link.'"></script>'."\n";
      }
    }

    if($thumbnail){
      $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/helpers/jquer.os_fancybox-thumbs.css';
      if(!preg_match_all('|jquer.os_fancybox-thumbs.css|',$head,$matches)){
        $head .= '<link rel="stylesheet" href="'.$link.'">'."\n";
      }

      $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/helpers/jquer.os_fancyboxGall-thumbs.js';
      if(!preg_match_all('|jquer.os_fancyboxGall-thumbs.js|',$head,$matches)){
        $head .= '<script type="text/javascript" src="'.$link.'"></script>'."\n";
      }
    }

    if($wheel){
      $link = JURI::base() . 'components/com_osgallery/assets/libraries/os_fancybox/helpers/jquer.mousewheel-3.0.6.pack.js';
      if(!preg_match_all('|jquer.mousewheel-3.0.6.pack.js|',$head,$matches)){
        $head .= '<script type="text/javascript" src="'.$link.'"></script>'."\n";
      }
    }
    $link = JURI::root() . "components/com_osgallery/assets/js/osGallery.main.js";
    if(!preg_match_all('|osGallery.main.js|',$head,$matches)){
      $head .= '<script type="text/javascript" src="'.$link.'"></script>'."\n";
    }

    return $head;
  }
  
  public function addMetaTags($head){
      global $os_gallery_configuration;
      
      $document = JFactory::getDocument();
    $db = JFactory::getDbo();
    $path = JURI::getInstance()->toString();
    $isSEF = JFactory::getConfig()->get('sef');
    $concat = ($isSEF) ? '?' : '&';
    $pathFragments = explode($concat, $path);
    $endFrag = end($pathFragments);
    $params = new JRegistry;

    $lang = JFactory::getLanguage();
    $tag_lang = $lang->getTag();

    if(stripos($endFrag, 'os_image_id') !== false){
        $endFrag = substr($endFrag, stripos($endFrag, 'os_image_id'));
        $image_id = preg_replace("/[^0-9]/", '', $endFrag);
    }else{
        return $head;
    }
    
    $query = "SELECT  gim.* , gal.id as galId FROM `#__os_gallery_img` as gim ".
                    "\n LEFT JOIN #__os_gallery_connect as gc ON gim.id=gc.fk_gal_img_id".
                    "\n LEFT JOIN #__os_gallery_categories as cat ON cat.id=gc.fk_cat_id ".
                    "\n LEFT JOIN #__os_gallery as gal ON gal.id=cat.fk_gal_id ".
                    "\n WHERE gim.id = ".(int) $image_id .
                    "\n ORDER BY cat.ordering ASC" ;


    $db->setQuery($query);
    $imageArr = $db->loadAssoc();

    if(isset($imageArr['galId'])){
        $galId = $imageArr['galId'];
        $query = "SELECT params FROM #__os_gallery WHERE id=$galId";
        $db->setQuery($query);
        $paramsString = $db->loadResult();
        $params->loadString($paramsString);
    }



    if($image_id && is_array($imageArr)){                                       

        if($imageArr['params'] != "{}" && !empty($imageArr['params'])){

            $imageArr['params'] = (array) json_decode(rawurldecode($imageArr['params']));

            $title = ($os_gallery_configuration->get('multilang', '0') == 1) ? $imageArr['params']['imgTitle_' . $tag_lang] : $imageArr['params']['imgTitle'];

            $description = ($os_gallery_configuration->get('multilang', '0') == 1) ? $imageArr['params']['imgShortDescription_' . $tag_lang] : $imageArr['params']['imgShortDescription'];
            $title = htmlspecialchars(strip_tags(substr($title, 0, 200)));
            $description = htmlspecialchars(strip_tags(substr($description, 0, 200)));
        }else{
            $title = htmlspecialchars(strip_tags(substr($document->getTitle(), 0, 200)));
            $description = htmlspecialchars(strip_tags(substr($document->getMetaData("description"), 0, 200)));
        }

        $config = JFactory::getConfig();
        $url = htmlspecialchars(JURI::getInstance()->toString());    
        $language = str_replace('-', '_', JFactory::getLanguage()->getTag());
        $siteName = htmlspecialchars($config->get('sitename'));
        $image_url = JURI::root(). "/images/com_osgallery/gal-" . $imageArr['galId'] . "/original/" . $imageArr['file_name'];


        // Type
      $head .= "<meta property='og:type' content=\"article\" />"."\n";
      //Url
      if ($url != '')
        $head .= "<meta property='og:url' content=\"$url\" />"."\n";
      //title
      if ($title != ''){
        $head .= "<meta property='og:title' content=\"$title\" />"."\n";
      }
      //description
      if ($description != ''){
        $head .= "<meta property='og:description' content=\"$description\" />"."\n";
      }else{
        $head .= "<meta property='og:description' content=\"$title\" />"."\n";
      }
      // Image
      if (isset($image_url) && $image_url!='') {
        $head .= "<meta property='og:image' content=\"$image_url\" />"."\n";
        $head .= "<meta property='og:image:width' content=\"900\" />"."\n";
        $head .= "<meta property='og:image:height' content=\"500\" />"."\n";
      }
      //Site Name

      if ($siteName != '')
          $head .= "<meta property='og:site_name' content=\"$siteName\" />"."\n";
          //end meta

        if($params->get('twitter_enable')){

          //Card
          $head .= "<meta property='twitter:card' content=\"summary_large_image\" />"."\n";
          $head .= "<meta property='twitter:url' content=\"$url\" />"."\n";
          //site
          if ($siteName != '')
            $head .= "<meta property='twitter:site' content=\"#$siteName\" />"."\n";
              //title
          if ($title != '')
            $head .= "<meta property='twitter:title' content=\"$title\" />"."\n";
          //description
          if ($description != '')
            $head .= "<meta property='twitter:description' content=\"$description\" />"."\n";
          //image
          if (isset($image_url) && $image_url!='')
            $head .= "<meta property='twitter:image:src' content=\"$image_url\" />"."\n";
        }

    }   
    return $head;
  }
}