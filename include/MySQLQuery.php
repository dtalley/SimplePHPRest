<?php

  /**
   * Class that allows a programmer to easily
   * build simple or complex SQL queries for
   * submission to a PostgreSQL database.
   */

  class MySQLQuery {
    
    private $_connection = NULL;

    private $_tables = array();
    private $_orders = array();
    private $_groups = array();
    private $_havings = array();
    private $_limit = 0;
    private $_offset = 0;

    private $_result = NULL;
    private $_rows = 0;
    private $_active = false;

    private $_additions = array();

    /**
     * Query must be provided an active
     * MySQLConnection object
     */
    public function __construct( 
      MySQLConnection $connection 
    ) {
      $this->_connection = $connection;
    }

    /**
     * Clear the current query, as if it
     * were just constructed.
     */
    public function clear() {
      $this->_tables = array();
      $this->_orders = array();
      $this->_groups = array();
      $this->_havings = array();
      $this->_limit = 0;
      $this->_offset = 0;
      $this->_additions = array();
    }

    /**
     * Open a specific database table under
     * a possible alias.
     */
    public function open(
      $name, 
      $alias = NULL 
    ) {
      $table = new MySQLTable( 
        $this, $name, $alias 
      );
      $this->_tables[] = $table;
      return $table;
    }

    /**
     * Order the returned rows by a specific
     * table column and direction.  Can be
     * called more than once to order by
     * more than one column.
     */
    public function order( 
      $column, $direction = "DESC" 
    ) {
      $this->_orders[] = $column . " " . $direction;
      return $this;
    }

    /**
     * Group the returned rows by a specific
     * database column.  Can be called more
     * than once to group by more than one.
     */
    public function group( $column ) {
      $this->_groups[] = $column;
      return $this;
    }

    /**
     * Limit the returned rows by a specific
     * offset and total number of rows to return.
     */
    public function limit( $limit, $offset ) {
      if( is_int( $limit ) && $limit >= 0 ) {
        $this->_limit = $limit;
      }
      if( is_int( $offset ) && $offset >= 0 ) {
        $this->_offset = $offset;
      }
      return $this;
    }

    /**
     * Only return rows where a specific condition
     * is met.
     */
    public function having( $condition ) {
      $this->_havings[] = $condition;
      return $this;
    }

    /**
     * Prepare a potentially tainted string for
     * inclusion into a query string.  Basically
     * just escapes it if it contains anything
     * that wouldn't be in an integer or float.
     */
    public function sanitize( $value ) {
      if( 
        preg_match( "/[^0-9\-.]/", $value ) 
      ) {
        $value = str_replace( 
          "'", "''", $value 
        );
        $value = str_replace( 
          "\\", "\\\\", $value 
        );
        $value = "'" . $value . "'";
      }
      return $value;
    }

    /**
     * Union another query onto this query.
     */
    public function union( $query ) {
      if( 
        is_object( $query ) && 
        get_class( $query ) === get_class( $this ) 
      ) {
        $add = "UNION " . $query->select_sql();
      } else {
        $add = "UNION " . $query;
      }
      $this->_additions[] = $add;
    }

    /**
     * Intersect this query with another.
     */
    public function intersect( $query ) {
      if( 
        is_object( $query ) && 
        get_class( $query ) == get_class( $this ) 
      ) {
        $add = "INTERSECT " . $query->select_sql();
      } else {
        $add = "INTERSECT " . $query;
      }
      $this->_additions[] = $add;
    }

    /**
     * Only return rows from this query that aren't
     * in the provided exception query.
     */
    public function except( $query ) {
      if( 
        is_object( $query ) && 
        get_class( $query ) == get_class( $this ) 
      ) {
        $add = "EXCEPT " . $query->select_sql();
      } else {
        $add = "EXCEPT " . $query;
      }
      $this->_additions[] = $add;
    }

    /**
     * Use the built query to select rows from
     * the database.
     */
    public function select() {
      /**
       * If this query is not currently active,
       * build the proper SQL string, or use
       * a provided SQL string if found.
       */
      if( $this->_active === false ) {
        $this->_active = true;
        $sql = $this->select_sql();
        $this->clear();
        if( !$sql && func_num_args() > 0 ) {
          $sql = func_get_arg(0);
        }
        $this->_result = $this->query( $sql );
        if( $this->_result === false ) {
          return $this->_result;
        }
      } else if( $this->_result === false ) {
        return NULL;
      }
      /**
       * Calculate the total number of rows
       * from the sent query.  If none are returned
       * return NULL.
       */
      if( $this->_rows == 0 ) {
        $this->_rows = $this->_connection->total( 
          $this->_result 
        );
        if( $this->_rows == 0 ) {
          return NULL;
        }
      }
      $array = $this->_connection->assoc( 
        $this->_result 
      );
      $this->_rows--;
      /**
       * If no more rows are available from the
       * resource, this query is essentially
       * finished doing its work, so we make
       * sure future calls to select() return
       * NULL and free the memory associated
       * with the result.
       */
      if( $this->_rows == 0 ) {
        $this->_connection->free( 
          $this->_result 
        );
        $this->_result = NULL;
      }
      return $array;
    }

    /**
     * Use the built query to insert rows into
     * the database.  The parameter indicates
     * whether or not a column used in the query
     * has a sequence applied to it.
     */
    public function insert( $sequential = true ) {
      /**
       * If this query is not currently active,
       * build the proper SQL string, or use
       * a provided SQL string if found.
       */
      if( $this->_active === false ) {
        $this->_active = true;
        $sql = $this->insert_sql();
        $this->clear();
        if( !$sql && func_num_args() > 0 ) {
          $sql = func_get_arg(0);
        }
        $this->_result = $this->query( $sql );
        if( !$this->_result ) {
          return $this->_result;
        }
        //Check the result status for success
        if( $this->_result !== false ) {
          /**
           * If this query involved a column with a
           * sequence applied to it, return the last
           * used value in that sequence.
           */
          if( $sequential ) {
            return $this->_connection->last();
          }
          return true;
        }        
        return false;
      }
    }

    /**
     * Use the built query to update specific
     * rows in the database.
     */
    public function update() {
      /**
       * If this query is not currently active,
       * build the proper SQL string, or use
       * a provided SQL string if found.
       */
      if( $this->_active === false ) {
        $this->_active = true;
        $sql = $this->update_sql();
        $this->clear();
        if( !$sql && func_num_args() > 0 ) {
          $sql = func_get_arg(0);
        }
        $this->_result = $this->query( $sql );
        if( !$this->_result ) {
          return $this->_result;
        }
        //Check the result status for success
        if( $this->_result !== false ) {
          return true;
        }
        return false;
      }
    }

    /**
     * Use the built query to delete specific
     * rows from the database.
     */
    public function delete() {
      /**
       * If this query is not currently active,
       * build the proper SQL string, or use
       * a provided SQL string if found.
       */
      if( $this->_active === false ) {
        $this->_active = true;
        $sql = $this->delete_sql();
        $this->clear();
        if( !$sql && func_num_args() > 0 ) {
          $sql = func_get_arg(0);
        }
        $this->_result = $this->query( $sql );
        if( !$this->_result ) {
          return $this->_result;
        }
        //Check the result status for success
        if( $this->_result !== false ) {
          return true;
        }
        return false;
      }
    }

    /**
     * Build the proper query for a SELECT statement.
     */
    public function select_sql() {
      $sql = "";
      $select_sql = "";
      $from_sql = "";
      $where_sql = "";
      $group_sql = "";
      $limit_sql = "";
      $order_sql = "";
      $having_sql = "";

      //Add the FROM section
      foreach( $this->_tables as $table ) {
        $select_add = $table->select_sql();
        $from_add = $table->from_sql();
        $where_add = $table->where_sql();
        if( $select_sql && $select_add ) {
          $select_sql .= ", ";
        }
        if( $from_sql && $from_add ) {
          $from_sql .= ", ";
        }
        $select_sql .= $select_add;
        $from_sql .= $from_add;
        $where_sql .= $where_add;
      }
      $i = 0;
      //Add the GROUP section if applicable
      foreach( $this->_groups as $group ) {
        if( $i > 0 ) {
          $group_sql .= ", ";
        }
        $group_sql .= $group;
        $i++;
      }
      //Add the LIMIT section if applicable
      if( $this->_limit || $this->_offset ) {
        $limit_sql = "LIMIT " . $this->_offset . ", " . $this->_limit;
      }
      $i = 0;
      //Add the ORDER section if applicable
      foreach( $this->_orders as $order ) {
        if( $i > 0 ) {
          $order_sql .= ", ";
        }
        $order_sql .= $order;
        $i++;
      }
      $i = 0;
      //Add the HAVING section if applicable
      foreach( $this->_havings as $having ) {
        if( $i > 0 ) {
          $having_sql .= ", ";
        }
        $having_sql .= $having;
        $i++;
      }

      //Build the final query string
      if( $select_sql ) {
        $sql .= "SELECT " . $select_sql . " ";
      }
      if( $from_sql ) {
        $sql .= "FROM " . $from_sql;
      }
      if( $where_sql ) {
        $sql .= " WHERE " . $where_sql;
      }
      if( $group_sql ) {
        $sql .= " GROUP BY " . $group_sql;
      }
      if( $order_sql ) {
        $sql .= " ORDER BY " . $order_sql;
      }
      if( $having_sql ) {
        $sql .= " HAVING " . $having_sql;
      }
      if( $limit_sql ) {
        $sql .= $limit_sql;
      }

      //Add any UNIONs, INTERSECTs, or EXCEPTs
      foreach( $this->_additions as $addition ) {
        $sql .= " " . $addition;
      }

      //Tack on the ending semicolon
      if( $sql ) {
        $sql .= ";";
      }
      
      return $sql;
    } // end select_sql()

    /**
     * Build the proper query for an INSERT statement
     */
    public function insert_sql() {
      $sql = "";
      $into_sql = "";
      $columns_sql = "";
      $values_sql = "";

      //Build the individual sections
      foreach( $this->_tables as $table ) {
        $into_add .= $table->from_sql();
        $columns_add = $table->columns_sql();
        $values_add = $table->values_sql();
        if( $into_sql && $into_add ) {
          $into_sql .= ", ";
        }
        if( $columns_sql && $columns_add ) {
          $columns_sql .= ", ";
        }
        if( $values_sql && $values_add ) {
          $values_sql .= ", ";
        }
        $into_sql .= $into_add;
        $columns_sql .= $columns_add;
        $values_sql .= $values_add;
      }

      //Build the final query
      if( $into_sql && $columns_sql && $values_sql ) {
        $sql .= "INSERT INTO " . $into_sql . " ";
        $sql .= "( " . $columns_sql . " ) ";
        $sql .= "VALUES ( " . $values_sql . " );";
      }
      
      return $sql;
    } // end insert_sql()

    /**
     * Build the proper query for an UPDATE statement
     */
    public function update_sql() {
      $sql = "";
      $from_sql = "";
      $update_sql = "";
      $where_sql = "";

      //Build the individual sections
      foreach( $this->_tables as $table ) {
        $from_add = $table->from_sql();
        $update_add = $table->update_sql();
        $where_add = $table->where_sql();
        if( $from_sql && $from_add ) {
          $from_sql .= ", ";
        }
        if( $update_sql && $update_add ) {
          $update_sql .= ", ";
        }
        $from_sql .= $from_add;
        $update_sql .= $update_add;
        $where_sql .= $where_add;
      }

      //Build the final query
      if( $from_sql ) {
        $sql .= "UPDATE " . $from_sql;
      }
      if( $update_sql ) {
        $sql .= " SET " . $update_sql;
      }
      if( $where_sql ) {
        $sql .= " WHERE " . $where_sql;
      }

      //Tack on the final semicolon
      if( $sql ) {
        $sql .= ";";
      }
      
      return $sql;
    } // end update_sql()

    /**
     * Build the proper query for a DELETE statement.
     */
    public function delete_sql() {
      $sql = "";
      $from_sql = "";
      $where_sql = "";

      //Build the individual sections
      foreach( $this->_tables as $table ) {
        $from_add = $table->from_sql();
        $where_add = $table->where_sql();
        if( $from_sql && $from_add ) {
          $from_sql .= ", ";
        }
        $from_sql .= $from_add;
        $where_sql .= $where_add;
      }

      //Build the final query
      if( $from_sql ) {
        $sql .= "DELETE FROM " . $from_sql;
      }
      if( $where_sql ) {
        $sql .= " WHERE " . $where_sql;
      }

      //Tack on the final semicolon
      if( $sql ) {
        $sql .= ";";
      }
      
      return $sql;
    } // end delete_sql()

    /**
     * Submit a query if the connection is
     * active, otherwise return NULL.
     */
    private function query( $sql ) {
      if( !$sql ) {
        return NULL;
      }
      $result = NULL;
      if( $this->_connection !== NULL ) {
        $result = $this->_connection->query(
          $sql 
        );
      }
      return $result;
    }

  }

  /**
   * Class used to model the details of each
   * query, namely tables, columns, values, and
   * their aliases.
   */
  class MySQLTable {
    
    private $_parent = NULL;
    private $_name = NULL;
    private $_query = NULL;
    private $_alias = NULL;
    private $_join = false;
    private $_direction = NULL;

    private $_tables = array();
    private $_selects = array();
    private $_wheres = array();
    private $_joins = array();
    private $_ons = array();
    private $_sets = array();

    /**
     * Adds a new table into a query, either
     * by using the result from an existing
     * query (a subquery), or from a database
     * table.
     */
    public function __construct( 
      $parent,
      $table, 
      $alias = NULL, 
      $join = false,
      $direction = NULL
    ) {
      /**
       * Parent is a refernece to the topmost
       * query object.
       */
      $this->_parent = $parent;
      /**
       * If the passed $table parameter is a
       * MySQLQuery object, then this table
       * is meant to be a subquery, otherwise
       * it's a normal table.
       */
      if( 
        is_object( $table ) &&
        get_class( $table ) == get_class( $parent )
      ) {
        $this->_query = $table;
      } else {
        $this->_name = $table;
      }
      $this->_alias = $alias;
      $this->_join = $join;
      $this->_direction = $direction;
    }

    /**
     * Add a column or columns to add to the
     * result rows.
     */
    public function select( 
      $columns, $alias = NULL 
    ) {
      $select = "";
      if( 
        is_object( $columns ) && 
        get_class( $columns ) === 
          get_class( $this->_parent )
      ) {
        $select .= "( ";
        $select .= $columns->select_sql();
        $select .= " )";
      } else {
        $select .= $this->parse( $columns );
      }
      if( $alias !== NULL ) {
        $select .= " AS " . $alias;
      }
      $this->_selects[] = $select;
    }

    /**
     * Only choose rows from this table where
     * a specific condition is met.
     */
    public function where() {
      $condition = $this->condition( 
        func_get_args() 
      );
      if( $condition ) {
        $this->_wheres[] = $condition;
      }
      return $this;
    }

    /**
     * Only choose rows from this table where
     * a specific condition is met.  Basically
     * the same as where() only these conditions
     * show up in the ON clause rather than
     * the WHERE clause.
     */
    public function on() {
      $condition = $this->condition( 
        func_get_args() 
      );
      if( $condition ) {
        $this->_ons[] = $condition;
      }
      return $this;
    }

    /**
     * Set a specific column in this table to a
     * given value.  Used for INSERT and UPDATE
     * queries only.
     */
    public function set( $column, $value ) {
      $assignment = new MySQLAssignment(
        $column, $value
      );
      $this->_sets[] = $assignment;
      return $this;
    }

    /**
     * Return a formatted column name with this
     * table's alias attached.
     */
    public function column( $name ) {
      if( $this->_alias !== NULL ) {
        return $this->_alias . "." . $name;
      }
      return $name;
    }

    /**
     * Join another table onto this table.
     */
    public function join(
      $name, 
      $alias = NULL, 
      $direction = NULL 
    ) {
      $table = new MySQLTable( 
        $this->_parent,
        $name, 
        $alias, 
        true, 
        $direction 
      );
      $this->_tables[] = $table;
      return $table;
    }

    /**
     * Return a string of comma separated columns
     * from this table as well as the columns from
     * any tables joined onto this one for use in a
     * SELECT statement.
     */
    public function select_sql() {
      $sql = "";
      $i = 0;
      foreach( $this->_selects as $select ) {
        if( $i > 0 ) {
          $sql .= ", ";
        }
        $sql .= $select;
        $i++;
      }
      foreach( $this->_tables as $table ) {
        $add = $table->select_sql();
        if( $sql && $add ) {
          $sql .= ", ";
        }
        $sql .= $add;
      }
      return $sql;
    }

    /**
     * Return this table's name formatted
     * for a FROM or INTO statement along with
     * any tables that were joined onto this one.
     */
    public function from_sql() {
      $sql = "";
      /**
       * If this table has been joined onto
       * another table, insert the proper
       * SQL to indicate that.
       */
      if( $this->_join ) {
        if( $this->_direction !== NULL ) {
          $sql .= " " . $this->_direction;
        }
        $sql .= " JOIN ";
        /**
         * If this table is a joined table, and it
         * in turn has tables joined onto it, then
         * we have to nest those joins.
         */
        if( count( $this->_tables ) > 0 ) {
          $sql .= " ( ";
        }
      }
      /**
       * If this table was meant as a subquery,
       * insert the stored query's select sql
       * as this table's FROM entry.
       */
      if( $this->_query !== NULL ) {
        $sql .= "( ";
        $sql .= $this->_query->select_sql();
        $sql .= " )";
      } else {
        $sql .= $this->_name;
      }
      /**
       * If an alias was provided for this
       * table, add that on as well.
       */
      if( $this->_alias !== NULL ) {
        $sql .= " AS " . $this->_alias;
      }
      /**
       * Add on the SQL for any tables that
       * were joined onto this one.
       */
      foreach( $this->_tables as $table ) {
        $sql .= $table->from_sql();
      }
      //Close the join nesting if necessary
      if( 
        $this->_join && 
        count( $this->_tables ) > 0 
      ) {
        $sql .= " )";
      }
      /**
       * Add on the ON clause and all of its
       * conditions.
       */
      if( count( $this->_ons ) > 0 ) {
        $sql .= " ON ";
        $i = 0;
        foreach( $this->_ons as $on ) {
          $sql .= $on;
        }
      }
      return $sql;
    } // end from_sql()

    /**
     * Return a formatted string of any WHERE
     * conditions applied to this table as well
     * as the WHERE conditions of any table that
     * was joined onto this one.
     */
    public function where_sql() {
      $sql = "";
      foreach( $this->_wheres as $where ) {
        $sql .= $where;
      }
      foreach( $this->_tables as $table ) {
        $sql .= $table->where_sql();
      }
      return $sql;
    }

    /**
     * Return a comma seperated list of columns
     * for use in INSERT statements.
     */
    public function columns_sql() {
      $sql = "";
      foreach( $this->_sets as $set ) {
        if( $sql ) {
          $sql .= ", ";
        }
        $sql .= $set->getColumn();
      }
      return $sql;
    }

    /**
     * Return a comma separated list of values
     * for use in INSERT statements.
     */
    public function values_sql() {
      $sql = "";
      foreach( $this->_sets as $set ) {
        if( $sql ) {
          $sql .= ", ";
        }
        $sql .= $set->getValue();
      }
      return $sql;
    }

    /**
     * Return a comma separated list of
     * key = value pairs for use in
     * UPDATE statements.
     */
    public function update_sql() {
      $sql = "";
      foreach( $this->_sets as $set ) {
        if( $sql ) {
          $sql .= ", ";
        }
        $sql .= $set->getColumn();
        $sql .= " = ";
        $sql .= $set->getValue();
      }
      return $sql;
    }

    /**
     * Parse a set of provided arguments
     * to build a condition to be used in
     * WHERE, ON, and UPDATE clauses.
     */
    private function condition( $args ) {
      $total = count($args);
      if( $total <= 0 ) {
        return false;
      }
      $condition = $args[0];
      $operator = NULL;
      $comparison = NULL;
      $joiner = NULL;
      /**
       * If one of the provided arguments
       * is AND or OR, and it isn't the
       * only argument, then this statement
       * should add that joiner to the end.
       */
      if( 
        $total > 1 &&
        in_array( 
          $args[$total-1], 
          array( "AND", "OR" ) 
        ) 
      ) {
        $joiner = $args[$total-1];
      }
      /**
       * If there are only 2 arguments and one of them
       * is not AND or OR, or if there are 3 arguments
       * and one of them is AND or OR, then the
       * intended operator must be =.
       */
      if( 
        ( $total == 2 && $joiner === NULL ) || 
        ( $total == 3 && $joiner !== NULL )
      ) {
        $operator = "=";
        $comparison = $args[1];
      /**
       * Otherwise if there are 3 arguments and
       * none of them are AND or OR, or if there
       * are 4 arguments, then an operator was
       * provided.
       */
      } else if( 
        ( $total == 3 && $joiner === NULL ) || 
        ( $total == 4 )
      ) {
        $operator = $args[1];
        $comparison = $args[2];
      }
      /**
       * If both an operator and comparison
       * were provided, add them to the initial
       * condition.
       */
      if( 
        $operator !== NULL && 
        $comparison !== NULL 
      ) {
        $condition .= " " . $operator;
        $condition .= " " . $comparison;
      }
      $condition = $this->parse( $condition );
      if( $joiner !== NULL ) {
        $condition .= " " . $joiner . " ";
      }
      return $condition;
    }

    /**
     * Parse a string for substrings enclosed with
     * {} and add on this table's alias in front
     * of those strings.
     */
    private function parse( $val ) {
      return preg_replace( 
        "/{([^}]+)}/", 
        $this->_alias . ".$1", 
        $val
      );
    }

  }

  /**
   * Helper class to keep track of column
   * value assignments in INSERT and UPDATE
   * statements.
   */
  class MySQLAssignment {
    
    private $_column = NULL;
    private $_value = NULL;

    /**
     * Set the column and value variables
     * to the provided values.
     */
    public function __construct( 
      $column, $value 
    ) {
      $this->_column = $column;
      $this->_value = $value;
    }

    //Return the stored column name
    public function getColumn() {
      return $this->_column;
    }

    //Return the stored value
    public function getValue() {
      return $this->_value;
    }

  }

?>