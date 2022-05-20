<?php
/**
 * @package    CComment
 * @author     Daniel Dimitrov <daniel@compojoom.com>
 * @date       26.08.15
 *
 * @copyright  Copyright (C) 2008 - 2015 compojoom.com . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

jimport('joomla.plugin.plugin');

/**
 * PlgContentJoscomment
 *
 * @since  5.0
 */
class PlgContentJoscomment extends JPlugin
{
	private static $control = null;

	/**
	 * Display the comment form onContentPrepare
	 *
	 * @param   string  $context  - the context
	 * @param   object  &$row     - the component row
	 * @param   object  &$params  - any params
	 *
	 * @return bool
	 *
	 * @since  5
	 */
	public function onContentPrepare($context, &$row, &$params)
	{
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer')
		{
			return true;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($row->text, 'ccomment') === false)
		{
			return true;
		}

		$regex = '/{ccomment\s+(.*?)}/i';
		preg_match_all($regex, $row->text, $matches, PREG_SET_ORDER);

		if (count($matches))
		{
			self::$control = $matches[0];
			$replace = '';

			if ($this->findOutComponent($context) == 'com_content' && $this->params->get('on_content_prepare', 0)
				&& $context == 'com_content.article')
			{
				$replace = $this->funkyStuff($row, $params, 'com_content');
			}

			$row->text = str_replace($matches[0][0], $replace, $row->text);
		}

		return true;
	}

	/**
	 * Display the comment form after the article
	 *
	 * @param   string  $context  - the context
	 * @param   object  &$row     - the component row
	 * @param   object  &$params  - any params
	 * @param   int     $page     - the page
	 *
	 * @return type
	 *
	 * @since  5
	 */
	public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
	{
		$input = JFactory::getApplication()->input;

		// Don't display comments if we are in print mode and the user doesn't want the comments there
		if ($input->getCmd('print') && !$this->params->get('printView', 0))
		{
			return false;
		}

		$default = 0;

		$component = $this->findOutComponent($context);

		if ($component == 'com_content')
		{
			$default = 1;
		}

		if ($component && $this->params->get('support_' . $component, $default))
		{
			JLoader::discover('ccommentHelper', JPATH_SITE . '/components/com_comment/helpers');

			// Let us find out what the control parameter has to do
			if (isset(self::$control))
			{
				$comments = '';

				// We need to handle com_content a little differently
				if ($component == 'com_content' && $context == 'com_content.article')
				{
					// If content_prepare is true, then we have already added the code to the $row->text in onContentPrepare
					if (!$this->params->get('on_content_prepare', 0))
					{
						$comments = $this->funkyStuff($row, $params, $component);
					}
				}
				else
				{
					$comments = $this->funkyStuff($row, $params, $component);
				}

				// Reset the controls
				self::$control = null;

				return $comments;
			}
			else
			{
				return ccommentHelperUtils::commentInit($component, $row, $params);
			}
		}

		return false;
	}

	/**
	 * Display the comment system in Matukio
	 *
	 * @param   string  $context  - the context
	 * @param   object  &$row     - the component row
	 * @param   object  &$params  - any params
	 * @param   int     $page     - the page
	 *
	 * @return bool|mixed|string|void
	 */
	public function onContentAfterButton($context, &$row, &$params, $page = 0)
	{
		if ($context == 'com_matukio.upcomingevent' && $this->params->get('support_com_matukio', 1))
		{
			JLoader::discover('ccommentHelper', JPATH_SITE . '/components/com_comment/helpers');

			return ccommentHelperUtils::commentInit('com_matukio', $row, $params);
		}

		return false;
	}

	/**
	 * Find out the component that we are integrating with
	 *
	 * @param   string  $context  - the context
	 *
	 * @return null|string
	 */
	private function findOutComponent($context)
	{
		$component = null;

		switch ($context)
		{
			case 'com_content.article':
			case 'com_content.featured':
			case 'com_content.category':
				$component = 'com_content';
				break;
			case 'com_virtuemart.productdetails';
				$component = 'com_virtuemart';
				break;
			case 'com_jdownloads.category':
			case 'com_jdownloads.download':
				$component = 'com_jdownloads';
				break;
			case 'com_matukio.event':
				$component = 'com_matukio';
				break;
		}

		return $component;
	}

	/**
	 * This function will be executed only if we have {ccomment on|off|closed} tag in the row->text
	 *
	 * @param   object  $row        - the component row
	 * @param   object  $params     - any params
	 * @param   string  $component  - the component name
	 *
	 * @return bool|mixed|string|void
	 */
	private function funkyStuff($row, $params, $component)
	{
		JLoader::discover('ccommentHelper', JPATH_SITE . '/components/com_comment/helpers');
		JLoader::discover('ccomment', JPATH_SITE . '/administrator/components/com_comment/library');
		$config = ccommentConfig::getConfig($component);

		// Save the default values
		$includeCats = $config->get('basic.include_categories', 0);
		$categories = $config->get('basic.categories', array());
		$excludeContentItems = $config->get('basic.exclude_content_items', array());
		$disableAdditional = $config->set('basic.disable_additional_comments', array());

		if (self::$control[1] == 'on')
		{
			// Temporary set everything to exclude and empty the content_items array
			$config->set('basic.include_categories', 0);
		}
		elseif (self::$control[1] == 'off')
		{
			// Temporary set everything to include and the content_items to empty
			$config->set('basic.include_categories', 1);
		}
		elseif (self::$control[1] == 'closed')
		{
			if ($component == 'virtuemart')
			{
				$id = $row->virtuemart_product_id;
			}
			else
			{
				$id = $row->id;
			}

			// Set include_categories to false in case comments are forbidden, but we have the comment tag
			$config->set('basic.include_categories', 0);

			// Add the item to the disabled comments array
			$config->set('basic.disable_additional_comments', array($id));
		}

		$config->set('basic.categories', array());
		$config->set('basic.exclude_content_items', array());

		$comments = ccommentHelperUtils::commentInit($component, $row, $params);

		// Set back the default values
		$config->set('basic.include_categories', $includeCats);
		$config->set('basic.categories', $categories);
		$config->set('basic.exclude_content_items', $excludeContentItems);
		$config->set('basic.disable_additional_comments', $disableAdditional);

		return $comments;
	}
}
