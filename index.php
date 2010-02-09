<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * The FSearch plugin provides MySQL full-text search capabilities.
 * 
 * IMPORTANT: Backup your database before using this plugin.
 *
 * The author of this plugin can not in any way be held responsible 
 * for any dataloss or losses of any other kind.
 * Please see the GNU General Public License for more details.
 *
 * @package frog
 * @subpackage plugin.fsearch
 *
 * @author Andrew Crookston <andrew@casystems.se>
 * @version 0.1.0
 * @since Frog version 0.9.5
 * @license http://www.gnu.org/licenses/gpl.html GPL License
 * @copyright Andrew Crookston, 2009
 *
 * @todo Only supports text in the Body-part. Should be able to choose fields via settings
 * @todo Known issue: Will not filter out markup like Textile or Markdown. Filters out HTML and PHP etc..
 * @todo Add a disable.php for rolling back the database changes when disabling the plugin
 */
Plugin::setInfos(array(
    'id'          => 'fsearch',
    'title'       => 'FSearch - Frog MySQL Search',
    'description' => 'Provides MySQL full-text search capabilities.', 
    'version'     => '0.1.0',
    'license'     => 'GPLv3',
    'author'      => 'Andrew Crookston (CA Systems)',
    'website'     => 'http://www.casystems.se',
    'update_url'  => 'http://www.casystems.se/frog-plugin-versions.xml',
    'require_frog_version' => '0.9.5', // may work with earlier versions. ONLY TESTED FOR 0.9.5!
));

// Load the FSearch class into the system
AutoLoader::addFile('FSearch', CORE_ROOT.'/plugins/fsearch/FSearch.php');

// Add observers for page editing
Observer::observe('view_page_edit_plugins', 'fsearch_display_select');
Observer::observe('part_edit_after_save',   'fsearch_clean_contents');


/**
 * Retrieve an array with all pages matching the search phrase.
 * 
 * @param Search $search A string containing MySQL full-text search query.
 * @return Array Returns an array of Page objects, if any.
 */
function fsearch($search) {
  $pages = FSearch::search(array(
      'search' => $search,
      'limit' => 10
  ));
  return $pages;
}

/**
 * Adds a 'Make searchable' checkbox on the edit page view in the backend.
 *
 * @param Page $page The object instance for the page that is being edited.
 */
function fsearch_display_select($page) {
  echo '<p><label for="page_is_fsearchable">'.__('Make this page searchable').': </label>'
      .'<select type="checkbox" value="1" id="page_is_fsearchable" name="page[is_fsearchable]" '
      .'<option value="1"'.(isset($page->is_fsearchable) && $page->is_fsearchable == '1' ? ' selected="selected"': '').'>Yes</option>'
      .'<option value="0"'.(!isset($page->is_fsearchable) || $page->is_fsearchable == '0' ? ' selected="selected"': '').'>No</option>'
      .'</select><br/>'
      .'<small>'.__('All code/tags will be stripped from the Body part and made searchable').'</small></p>';
}


/**
 * Executes on saving a page_part.
 * Cleans out any code (Not Textile or Markdown) and adds it to a dedicated search field.
 *
 * @param PagePart $page_part The object instance for the page_part that is being edited.
 */
function fsearch_clean_contents($page_part) {
  global $__FROG_CONN__;
  // Currently we only support searching in the Body-part (This is in the @todo)
  if ($page_part->name == 'body') {
    $page = Page::findById($page_part->page_id);
    $title = '';
    if ($page) {
      $title = $page->title;
    }
    $sql = 'UPDATE '.TABLE_PREFIX.'page_part '.
           ' SET content_fsearchable = '.$__FROG_CONN__->quote($title."\n\n".strip_tags($page_part->content)).' '.
           ' WHERE id='.(int)$page_part->id;
    $__FROG_CONN__->exec($sql);
  }
}

