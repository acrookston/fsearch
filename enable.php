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
 * @package frog
 * @subpackage plugin.fsearch
 *
 * @author Andrew Crookston <andrew@casystems.se>
 * @version 0.1.0
 * @since Frog version 0.9.5
 * @license http://www.gnu.org/licenses/gpl.html GPL License
 * @copyright Andrew Crookston, 2009
 */

$PDO    = Record::getConnection();
$driver = strtolower($PDO->getAttribute(Record::ATTR_DRIVER_NAME));


// This plugin only works for MySQL!
if ($driver == 'mysql') {
  
  // Add the page attribute is_fsearchable
  $PDO->exec("ALTER TABLE `".TABLE_PREFIX."page` ADD `is_fsearchable` tinyint(1) NOT NULL default '0'");
  
  // Add the page_part text field for search and create index
  $PDO->exec("ALTER TABLE `".TABLE_PREFIX."page_part` ADD `content_fsearchable` longtext NULL");
  $PDO->exec("ALTER TABLE `".TABLE_PREFIX."page_part` ADD FULLTEXT `fsearchable_ix` (`content_fsearchable`)");

}

