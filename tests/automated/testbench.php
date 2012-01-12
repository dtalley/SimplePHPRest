<?php

  class TestBench {
    
    private $_collection = "";
    private $_current = "";
    private $_number = 1;
    private $_assertions = array();

    public function start( $collection ) {
      $this->_collection = $collection;
    }

    public function prepare( $title ) {
      $this->_current = $title;
    }

    public function assert( 
      $value, $comparison 
    ) {
      $assertion = new TestAssertion( 
        $this->_current + " #" + $this->_number, 
        $value, 
        $comparison 
      );
      $this->_number++;
      $this->_assertions[] = $assertion;
      return $assertion->getResult();
    }

    public function dump() {
      $ret = "";
      $ret .= "<strong>";
      $ret .= $this->_collection;
      $ret .= "</strong><br /><br />";
      foreach( $this->_assertions as $test ) {
        $ret .= $test->print() . "<br />";
      }
      return $ret;
    }

  }

  class TestAssertion {
    
    private $_title = "";

    private $_value = NULL;
    private $_comparison = NULL;
    private $_result = false;

    public function __construct( 
      $title, $value, $comparison 
    ) {
      $this->_title = $title;
      $this->_result = ( $value == $comparison );
      $this->_value = $value;
      $this->_comparison = $comparison;
    }

    public function getResult() {
      return $this->_result;
    }

    public function print() {
      $ret = "";
      if( $this->_result ) {
        $ret .= "<span style=\"color:green;\">";
        $ret .= $this->_title . " PASSED!</span>";
      } else {
        $ret .= "<span style=\"color:red;\">";
        $ret .= $this->_title . " FAILED!</span><br />";
        $ret .= "<strong>Value:</strong><br />";
        $ret .= $this->_value . "<br />";
        $ret .= "<strong>Comparison:</strong><br />";
        $ret .= $this->_comparison;
      }
      return $ret;
    }

  }

?>