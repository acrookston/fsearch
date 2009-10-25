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

/**
 * class FSearch
 *
 * Retrieves pages and searches page parts and page titles.
 *
 * @author Andrew Crookston <andrew@casystems.se>
 * @since Frog version 0.9.5
 */
class FSearch extends Record {
  
  const TABLE_NAME = 'page';
  
  /**
   * class FSearch
   *
   * Retrieves pages and searches page parts and page titles.
   *
   * @author Andrew Crookston <andrew@casystems.se>
   * @since Frog version 0.9.5
   */
  public static function search($args = null) {
    // Collect attributes...
    $where    = isset($args['where']) ? trim($args['where']) : '';
    $search   = isset($args['search']) ? trim($args['search']) : '';
    $order_by = isset($args['order']) ? trim($args['order']) : '';
    $offset   = isset($args['offset']) ? (int) $args['offset'] : 0;
    $limit    = isset($args['limit']) ? (int) $args['limit'] : 0;
    
    // Prepare query parts
    $order_by_string = empty($order_by) ? '' : "ORDER BY $order_by";
    $limit_string    = $limit > 0 ? "LIMIT $offset, $limit" : '';
    
    // Prepare the WHERE string (It's WHERE all the magic happens) ;)
    $where_string   = empty($where) ? 'WHERE ' : "WHERE $where AND "; // Allow custom queries
    $where_string   .= "(page.is_fsearchable = 1 AND page_part.name = 'body' AND page.needs_login = 2 " // Security
                    .  ' AND MATCH (page_part.content_fsearchable) AGAINST (\''.(string)$search.'\'))'; // full text
    
    $tablename      = self::tableNameFromClassName('FSearch');
    $tablename_user = self::tableNameFromClassName('User');
    $tablename_part = self::tableNameFromClassName('PagePart');
    
    // Prepare SQL
    $sql = "SELECT page.*, creator.name AS created_by_name, updator.name AS updated_by_name FROM $tablename AS page".
           " LEFT JOIN $tablename_user AS creator ON page.created_by_id = creator.id".
           " LEFT JOIN $tablename_user AS updator ON page.updated_by_id = updator.id".
           " LEFT JOIN $tablename_part AS page_part ON page.id = page_part.page_id".
           " $where_string $order_by_string $limit_string";
    $stmt = self::$__CONN__->prepare($sql);
    $stmt->execute();
    
    $objects = array();
    while ($obj = $stmt->fetchObject('FSearch')) {
      $parent = self::findParentById($obj->parent_id);
      $obj->part = get_parts($obj->id);
      $objects[] = new Page($obj, $parent);
    }
    return $objects;
  }
  
  /**
   * method findPage
   *
   * This method a copy of model Page::find
   * It's a copy because the model Page is not publicly available in the front-end.
   * A slight modification is made, the Object names are changed to FSearch for compatibility.
   *
   * @author Philippe Archambault <philippe.archambault@gmail.com>
   * @author Martijn van der Kleijn <martijn.niji@gmail.com>
   * @since Frog version 0.1
   */
  public static function find($args) {
    // Collect attributes...
    $where    = isset($args['where']) ? trim($args['where']) : '';
    $order_by = isset($args['order']) ? trim($args['order']) : '';
    $offset   = isset($args['offset']) ? (int) $args['offset'] : 0;
    $limit    = isset($args['limit']) ? (int) $args['limit'] : 0;
    
    // Prepare query parts
    $where_string = empty($where) ? '' : "WHERE $where";
    $order_by_string = empty($order_by) ? '' : "ORDER BY $order_by";
    $limit_string = $limit > 0 ? "LIMIT $offset, $limit" : '';
    
    $tablename = self::tableNameFromClassName('FSearch');
    $tablename_user = self::tableNameFromClassName('User');
    
    // Prepare SQL
    $sql = "SELECT page.*, creator.name AS created_by_name, updator.name AS updated_by_name FROM $tablename AS page".
           " LEFT JOIN $tablename_user AS creator ON page.created_by_id = creator.id".
           " LEFT JOIN $tablename_user AS updator ON page.updated_by_id = updator.id".
           " $where_string $order_by_string $limit_string";
    
    $stmt = self::$__CONN__->prepare($sql);
    $stmt->execute();

    // Run!
    if ($limit == 1) {
      $obj = $stmt->fetchObject('FSearch');
      $parent = self::findParentById($obj->parent_id);
      return new Page($obj, $parent);
    } else {
      $objects = array();
      while ($obj = $stmt->fetchObject('FSearch')) {
        $parent = self::findParentById($obj->parent_id);
        $objects[] = new Page($obj, $parent);
      }
      return $objects;
    }
  }
  
  /**
   * method findById
   *
   * This method a copy of model Page::findById
   * It's a copy because the model Page is not publicly available in the front-end.
   * A slight modification is made, the Object names are changed to FSearch for compatibility
   *
   * @author Philippe Archambault <philippe.archambault@gmail.com>
   * @author Martijn van der Kleijn <martijn.niji@gmail.com>
   * @since Frog version 0.1
   */
  public static function findById($id) {
    return self::find(array(
        'where' => 'page.id='.(int)$id,
        'limit' => 1
    ));
  }
  
  /**
   * method findParentById
   *
   * Methods finds the parent.
   * Will return false if id == 0 since there is no parent for page id 0
   *
   * @author Andrew Crookston <andrew@casystems.se>
   * @since Frog version 0.9.5
   */
  public static function findParentById($id) {
    if ($id == 0) { return false; }
    
    return self::find(array(
        'where' => 'page.id='.(int)$id,
        'limit' => 1
    ));
  }
}
