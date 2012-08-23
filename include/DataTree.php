<?php

if( !defined( "DATATREE_LOADED" ) ) {
  define( "DATATREE_LOADED", true );
}

/**
 * Useful for keeping track of a tree-like 
 * collection of data, for templates, API 
 * returns, and accessing it with simple, 
 * powerful paths, much like a file structure 
 * or XPath.
*/
class DataTree {
  
  private $_elements = array();
  private $_values = array();

  private $_parent = NULL;
  private $_level = 0;

  function __construct( $parent = NULL, $level = 0 ) {
    $this->_parent = $parent;
    $this->_level = $level;
  }
  function __destruct() {}

  protected function setParent( $parent ) {
    if( $this->_parent !== NULL ) {
      $this->_parent->delete( $this );
    }
    $this->_parent = $parent;
  }

  protected function setLevel( $level ) {
    $this->_level = $level;
  }

  public function isEmpty() {
    return (
      count( $this->_elements ) + 
      count( $this->_values )
    ) == 0;
  }

  /**
   * Create a valid path through this
   * object.  If the path doesn't
   * already exist, create it.  If the
   * destination node already exists, fork 
   * the tree at that point and create a new
   * path.
   * @param $path The path string to create
   * @param $forceArray Force the current element
   * to start as an array, rather than waiting for
   * a subsequent element to be added
   */
  public function start( $path, $forceArray = false ) {
    //If $path is not a string, somebody screwed up
    if( !is_string( $path ) ) {
      return NULL;
    }
    //If $path is empty, we've arrived at our destination
    if( strlen( $path ) == 0 ) {
      return $this;
    }
    $this->parse( $path, $name, $filters );
	$total_filters = count($filters);
    if( $name == ".." ) {
      return $this->_parent->start( $path );
    }
    //If the requested element doesn't exist, create it
    if( 
      !isset( $this->_elements[$name] ) && 
      $forceArray
    ) {
      $this->_elements[$name] = array();
    }
    /**
     * If there's already a value with the specified
     * name, delete it, because it will be
     * inaccessible anyway.
     */
    if( isset( $this->_values[$name] ) ) {
      trigger_error( 
        "Overwrote data value with data element.", 
        E_USER_WARNING
      );
      unset( $this->_values[$name] );
    }
    /**
     * If we're at our destination, we have to fork
     * the tree and create a new path.  Otherwise,
     * if we're not at our destination but the
     * requested path doesn't exist, fork the tree
     * to create it.
     */
    if( 
      ( 
        strlen( $path ) == 0 && 
        $total_filters == 0 
      ) ||
      !$this->initialized( $this->_elements[$name] )
    ) {
      if( 
        isset( $this->_elements[$name] ) && 
        !is_array( $this->_elements[$name] ) 
      ) {
        $this->_elements[$name] = array(
          $this->_elements[$name]
        );
      }
      if( 
        isset( $this->_elements[$name] ) && 
        is_array( $this->_elements[$name] ) 
      ) {
        $this->_elements[$name][] = new DataTree( 
          $this, 
          $this->_level+1
        );  
      } else {
        $this->_elements[$name] = new DataTree( 
          $this, 
          $this->_level+1
        );  
      }
    }
    $elements = $this->elements( $name, $filters );
    $total = count( $elements );
    return $elements[$total-1]->start( $path );
  } //end start

  /**
   * Check if a provided node is a valid node, in
   * other words, it's an initialized array with
   * at least one element in it, or it is a
   * DataTree object itself.
   */
  private function initialized( $element ) {
    if( !$element ) {
      return false;
    } else if( 
      is_array( $element ) && 
      count( $element ) == 0 
    ) {
      return false;
    } else if( 
      is_object( $element ) &&
      get_class( $element ) != get_class( $this ) 
    ) {
      return false;
    }
    return true;
  }

  /**
   * Store a value by name within this tree.
   */
  public function store( $name, $value ) {
    //We can't store a value into nothingness
    if( !is_string( $name ) || !$name ) {
      return $this;
    }
    /**
     * If the provided value is an instance of this
     * class, we should store it as an element
     * rather than as a value.
     */
    if( 
      is_object( $value ) && 
      get_class( $value ) == get_class( $this )
    ) {
      if( 
        isset( $this->_elements[$name] ) && 
        !is_array( $this->_elements[$name] ) 
      ) {
        $this->_elements[$name] = array(
          $this->_elements[$name]
        );
      }
      if( !isset( $this->_elements[$name] ) ) {
        $this->_elements[$name] = $value;
      } else {
        $this->_elements[$name][] = $value;
      }
      $value->setParent( $this );
      $value->setLevel( $this->_level+1 );
    } else {
      /**
       * If an element exists with this name,
       * this value would be inaccessible anyway,
       * so don't perform the store.
       */
      if( isset( $this->_elements[$name] ) ) {
        return $this;
      }
      $this->_values[$name] = $value;
    }
    return $this;
  } //end store

  /**
   * Delete a valid path or direct descendant
   * from this tree.
   */
  public function delete( $node ) {
    /**
     * If the provided argument is a
     * tree object, we know it's a direct
     * descendant of this tree, so
     * delete it from the elements array.
     */
    if( 
      is_object( $node ) && 
      get_class( $node ) == get_class( $this ) 
    ) {
      foreach( 
        $this->_elements as $name => $elements 
      ) {
        $total = 0;
        if( is_array( $this->_elements[$name] ) ) {
          $total = count( $this->_elements[$name] );
        } else if( is_object( $this->_elements[$name] ) ) {
          $total = 1;
        }
        for( $i = 0; $i < $total; $i++ ) {
          if( is_array( $this->_elements[$name] ) ) {
            $element = $this->_elements[$name][$i];
          } else {
            $element = $this->_elements[$name];
          }
          if( $element == $node ) {
            if( is_array( $this->_elements[$name] ) ) {
              array_splice( 
                $this->_elements[$name], 
                $i, 
                1 
              );
            }
            /**
             * If the elements array that the
             * provided object belongs to is now
             * empty, delete it entirely.
             */
            if( $total == 1 ) {
              unset( $this->_elements[$name] );
            }
            return $this;
          }
        }
      }
    } else {
      $path =& $node;
      $this->parse( $path, $name, $filters );
      if( $name == ".." ) {
        if( $this->_parent === NULL ) {
          return NULL;
        }
        return $this->_parent->delete( $path );
      }
      if( strlen( $path ) == 0 ) {
        if( isset( $this->_elements[$name] ) ) {
          $elements = $this->elements( $name, $filters );
          foreach( $elements as $element ) {
            if( is_array( $this->_elements[$name] ) ) {
              array_splice( 
                $this->_elements[$name], 
                array_search( 
                  $element, $this->_elements[$name] 
                ), 
                1 
              );
              if( 
                count( $this->_elements[$name] ) == 0 
              ) {
                unset( $this->_elements[$name] );
              }
            } else if( 
              $element == $this->_elements[$name] 
            ) {
              unset( $this->_elements[$name] );
            }
          }
        }
        if( isset( $this->_values[$name] ) ) {
          unset( $this->_values[$name] );
        }
        return $this;
      } else {
        $elements = $this->elements( $name, $filters );
        $total = count( $elements );
        if( $total > 0 ) {
          return $elements[$total-1]->delete( $path );
        }
        return $this;
      }
    }
  } //end delete

  /**
   * Get a value or tree object from within
   * this tree, as specified by a path.
   * @param $path The path to search for
   */
  public function get( $path ) {
    //If $path is empty, we're at our destination
    if( strlen( $path ) == 0 ) {
      return $this;
    }
    $this->parse( $path, $name, $filters );
    if( $name == ".." ) {
      if( $this->_parent === NULL ) {
        return NULL;
      }
      return $this->_parent->get( $path );
    }
    /**
     * First try to follow the requested path
     * via an element.
     */
    if( isset( $this->_elements[$name] ) ) {
      $elements = $this->elements( $name, $filters );
      $total = count( $elements );
      if( $total > 0 ) {
        return $elements[$total-1]->get( $path );
      }
    } else if( isset( $this->_values[$name] ) ) {
      return $this->_values[$name];
    }
    return NULL;
  } //end get

  /**
   * Count the number of elements that are
   * found at a specific path and return
   * that number.
   * @param $path The path to search for
   * @return int The number of elements
   * at the requested path destination
   */
  public function count( $path ) {
    if( strlen( $path ) == 0 ) {
      return 0;
    }
    $this->parse( $path, $name, $filters );
    if( $name == ".." ) {
      if( $this->_parent === NULL ) {
        return 0;
      }
      return $this->_parent->count( $path );
    }
    if( isset( $this->_elements[$name] ) ) {
      $elements = $this->elements( $name, $filters );
      $total = count( $elements );
      //If we're at our destination node, return the count
      if( strlen( $path ) == 0 ) {
        return $total; 
      }
      if( $total > 0 ) {
        return $elements[$total-1]->count( $path );
      }
    } else if( isset( $this->_values[$name] ) ) {
      //If no element exists, but a value exists
      return 1;
    }
    return 0;
  } //end count

  /**
   * Save out this tree's contents in one
   * of the several supported formats.
   */
  public function save( $format ) {
    //JavaScript Object Notation
    if( $format == "json" ) {
      return $this->save_json();
    //eXtensible Markup Language
    } else if( $format == "xml" ) {
      return $this->save_xml();
    }
    return "";
  } //end save

  /**
   * Save this tree's contents in JSON
   * (JavaScript Object Notation) format.
   */
  protected function save_json( $as_string = true ) {
    $return = array();
    //Loop through and insert each element
    foreach( $this->_elements as $name => $tree ) {
      if( !isset( $return[$name] ) ) {
        if( is_array( $this->_elements[$name] ) ) {
          $return[$name] = array();
        }
      }

      $total = 0;
      if( is_array( $this->_elements[$name] ) ) {
        $total = count( $this->_elements[$name] );
      } else if( is_object( $this->_elements[$name] ) ) {
        $total = 1;
      }

      for( $i = 0; $i < $total; $i++ ) {
        if( is_array( $this->_elements[$name] ) ) {
          $element = $this->_elements[$name][$i];
          $return[$name][] = (
            $element->save_json( false )
          );
        } else {
          $element = $this->_elements[$name];
          $return[$name] = (
            $element->save_json( false )
          );
        }
      }
    }
    //Loop through and insert each value
    foreach( $this->_values as $name => $value ) {
      /**
       * Don't return this value if an
       * element with the same name exists.
       */
      if( !isset( $return[$name] ) ) {
        $return[$name] = $value;
      }
    }

    if( count( $return ) == 0 ) {
      return NULL;
    }
    if( !$as_string ) {
      return $return;
    }
    return json_encode( $return );
  } //end save_json

  /**
   * Save this tree's contents in XML
   * eXtensible Markup Language format.
   */
  protected function save_xml() {
    $return = "";
    //Loop through and insert each element
    foreach( $this->_elements as $name => $tree ) {
      $total = 0;
      if( is_array( $this->_elements[$name] ) ) {
        $total = count( $this->_elements[$name] );
      } else if( is_object( $this->_elements[$name] ) ) {
        $total = 1;
      }
      for( $i = 0; $i < $total; $i++ ) {
        if( is_array( $this->_elements[$name] ) ) {
          $element = $this->_elements[$name][$i];
        } else {
          $element = $this->_elements[$name];
        }
        $return .= "<" . $name . ">";
        $return .= $element->save_xml();
        $return .= "</" . $name . ">";
      }
    }
    //Loop through and insert each value
    foreach( $this->_values as $name => $value ) {
      /**
       * Don't return this value if an
       * element with the same name exists.
       */
      if( !isset( $this->_elements[$name] ) ) {
        $return .= "<" . $name . ">";
        $return .= $value;
        $return .= "</" . $name . ">";
      }
    }
    return $return;
  } //end save_xml

  /**
   * Test this tree against the provided set of
   * filters.
   */
  protected function match( array $filters ) {
    $test = array();
    $operators = array();
    foreach( $filters as $filter ) {
      if( is_array( $filter ) ) {
        $test[] = $this->match( $filter );
      } else if( 
        $filter == "and" || 
        $filter == "or"
      ) {
        $operators[] = $filter;
      } else {
        $test[] = $this->test( $filter );
      }
    }
    $total = count( $test );
    $result = $test[0];
    for( $i = 1; $i < $total; $i++ ) {
      if( $operators[$i-1] == "and" ) {
        $result = $result && $test[$i];
      } else if( $operators[$i-1] == "or" ) {
        $result = $result || $test[$i];
      }
    }
    return $result;
  } //end match

  /**
   * Test a condition against this tree.
   * A condition is anything in the form:
   * path|operator|value, without the pipes.
   * For example: name=Fred.  The value can also
   * be enclosed in single quotes, like:
   * name='Fred'.  The operator can be any
   * equality or inequality operator.  The
   * value can only be a string or a number.
   */
  protected function test( $condition ) {
    $random = "";
    while( strlen( $random ) < 7 ) {
      $random .= rand(0,9) . "";
    }
    //This is to protect against escaped single quotes
    $condition = str_replace( 
      "\\'", 
      $random, 
      $condition 
    );
    //Check if the value is enclosed by single quotes
    preg_match( 
      "/'([^']*?)'/", 
      $condition, 
      $strings 
    );
    $string = (
      isset( $strings[1] ) ? 
      $strings[1] : 
      false
    );
    /**
     * If it is, store the value inside the 
     * quotes and replace it with a token 
     * that we can replace again later.  This 
     * makes sure nothing inside the single 
     * quotes messes with our string splitting
     * in a bit.
     */
    if( $string !== false ) {
      $condition = str_replace( 
        "'" . $string . "'", 
        "[!!]", 
        $condition
      );
      $string = str_replace( 
        $random, 
        "'", 
        $string 
      );
    }
    //Check for a valid operator
    preg_match( 
      "/\s*(=|>|<|<=|>=)\s*/", 
      $condition, 
      $operators
    );
    $operator = (
      isset( $operators[1] ) ? 
      trim( $operators[1] ) : 
      false
    );
    /**
     * If a filter doesn't have an operator in it,
     * it's not a properly formatted filter.
     */
    if( $operator === false ) {
      throw new Exception( 
        "No operator found in filter '" . 
        $condition . 
        "'."
      );
    }
    $split = preg_split( 
      "/\s*(=|>|<|<=|>=)\s*/", 
      $condition
    );
    $path = $split[0];
    $this->parse( $path, $name, $filters );
    $value = $split[1];
    //If we pulled out a string, inject it now
    if( $string ) {
      $value = str_replace( "[!!]", $string, $value );
    }
    //If $path is null, we've reached the destination
    if( strlen( $path ) == 0 ) {
      //If the name can't be found, this can't be true
      if( !isset( $this->_values[$name] ) ) {
        return false;
      }
      $test = $this->_values[$name];
      //If the value is a number, cast it for comparison
      if( !preg_match( "/[^0-9.]/", $value ) ) {
        if( strpos( ".", $value ) >= 0 ) {
          $value = (float)$value;
        } else {
          $value = (int)$value;
        }
      }
      if( 
        $operator == "=" && 
        $test == $value 
      ) {
        return true;
      } else if( 
        $operator == ">" && 
        $test > $value 
      ) {
        return true;
      } else if( 
        $operator == "<" && 
        $test < $value 
      ) {
        return true;
      } else if( 
        $operator == ">=" && 
        $test >= $value 
      ) {
        return true;
      } else if( 
        $operator == "<=" && 
        $test <= $value 
      ) {
        return true;
      }
      return false;
    } else {
      $elements = $this->elements( $name, $filters );
      $total = count( $elements );
      if( $total > 0 ) {
        if( $string ) {
          $value = "'" . $value . "'";
        }
        return $elements[$total-1]->test( 
          $path . " " . $operator . " " . $value 
        );
      }
    }
    return false;
  } //end test

  /**
   * Return an array of elements
   * that match the given name and
   * provided set of filters
   */
  private function elements( $name, $filters ) {
    if( $name == ".." ) {
      if( $this->_parent === NULL ) {
        return array();
      }
      return array($this->_parent);
    }
    if( !isset( $this->_elements[$name] ) ) {
      return array();
    }
    $total = 0;
    if( is_array( $this->_elements[$name] ) ) {
      $total = count( $this->_elements[$name] );
    } else if( is_object( $this->_elements[$name] ) ) {
      $total = 1;
    }
    if( count( $filters ) > 0 ) {
      if( count( $filters ) == 1 ) {
        //If the filter is just a number, it's an index
        if( 
          !preg_match( 
            "/[^0-9]/", 
            $filters[0] 
          ) 
        ) {
          $index = (int)$filters[0];
          if( 
            (
              is_array( $this->_elements[$name] ) &&
              isset( $this->_elements[$name][$index] ) 
            ) ||
            $index == 0
          ) {
            if( is_array( $this->_elements[$name] ) ) {
              return array( 
                $this->_elements[$name][$index] 
              );
            } else {
              return array(
                $this->_elements[$name]
              );
            }
          }
          return NULL;
        }
      }
      $return = array();
      //Loop through and check all the filters
      for( $i = 0; $i < $total; $i++ ) {
        if( is_array( $this->_elements[$name] ) ) {
          $element = $this->_elements[$name][$i]; 
        } else {
          $element = $this->_elements[$name];
        }
        if( $element->match( $filters ) ) {
          $return[] = $element;
        }
      }
      return $return;
    }
    if( is_array( $this->_elements[$name] ) ) {
      return $this->_elements[$name];
    }
    return array( $this->_elements[$name] );
  } //end elements

  /**
   * Parse through a path string and find the 
   * immediate name, any filters that need to 
   * be applied to the immediate name, and the 
   * excess path once those elements are removed.
   * @param $path The path to parse
   * @param $name The immediate name that's pulled 
   * from the path
   * @param $filters An array of filters that's 
   * pulled from the end of the immediate name
  */
  private function parse( 
    &$path, 
    &$name, 
    &$filters 
  ) {
    /**
     * Trim any excess forward slashes from the
     * beginning of the path string.  They are not
     * needed.
     */
    while( substr( $path, 0, 1 ) == "/" ) {
      $path = substr( $path, 1 );
    }
    /**
     * Match any string with letters, 
     * numbers, *, _ and - at the beginning 
     * of the path string.  This is the 
     * immediate name.
     */
    preg_match( 
      "/^([a-zA-Z0-9*_\-]+)/", 
      $path, 
      $matches
    );
    $name = (
      isset( $matches[1] ) ? 
      $matches[1] : 
      false
    );
    /**
     * If the name wasn't found, something
     * is wrong with the path.
     */
    if( $name === false ) {
      throw new Exception( 
        "Malformed path."
      );
    }
    /**
     * Remove the extracted immediate name
     * from the path string.
     */
    $path = preg_replace( 
      "/" . $name . "/", 
      "", 
      $path, 
      1
    );
    $data = "";
    /**
     * If the remaining path begins with a [ character,
     * then we have a filter to deal with.
     */
    if( substr( $path, 0, 1 ) == "[" ) {
      /**
       * We match the content at the beginning of the
       * remaining path that's between a [ and a ],
       * proceeded by a / or the end of the path string
       */
      preg_match(
        "/^\[(.+)\](\/|\$)/s",
        $path,
        $matches
      );
      $data = (
        isset( $matches[1] ) ? 
        $matches[1] : 
        false
      );
      /**
       * If the filter data isn't found, something is
       * wrong with the path string.
       */
      if( $data === false ) {
        throw new Exception( 
          "Malformed filter string." 
        );
      }
    }
    $filters = array();
    if( strlen( $data ) > 0 ) {
      //Escape the filter data so it'll play nice with regexp
      $replace = preg_quote( $data );
      $path = preg_replace( 
        "/\[" . $replace . "\]/", 
        "", 
        $path, 
        1 
      );
      
      preg_match_all( 
        "/(\s+and|or\s+)/i", 
        $data, 
        $matches 
      );
      $split = preg_split( "/\s+and|or\s+/i", $data );
      $total = count( $split );
      $stack = array();
      $level = 0;
      for( $i = 0; $i < $total; $i++ ) {
        $condition = trim( $split[$i] );
        $open_count = substr_count( $condition, "(" );
        $close_count = substr_count( $condition, ")" );
        while( substr( $condition, 0, 1 ) == "(" ) {
          $stack[] = $filters;
          $filters = array();
          $condition = substr( $condition, 1 );
          $level++;
        }
        $use = $condition;
        if( $close_count > $open_count ) {
          for( 
            $i = 0; 
            $i < $close_count - $open_count; 
            $i++ 
          ) {
            $use = substr( $use, 0, strlen( $use ) - 1 );
          }
        }
        $filters[] = $use;
        while( 
          count( $stack ) > 0 && 
          substr( $condition, -1, 1 ) == ")"
        ) {
          $new = array_pop( $stack );
          $new[] = $filters;
          $filters = $new;
          $condition = substr( 
            $condition, 
            0, 
            strlen( $condition ) - 1 
          );
          $level--;
        }
        if( $i < $total - 1 ) {
          $filters[] = trim( $matches[1][$i] );
        }
      }
    }
  } //end parse

}

?>