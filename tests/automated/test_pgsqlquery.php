<?php

  require_once "testbench.php";
  require_once "../include/PGSQLQuery.php";

  $bench = new TestBench();
  $bench->start( "PGSQL Query Builder Tests" );

  $q = new PGSQLQuery();
    
  $p = $q->open( "blog_posts", "p" );
  $p->order( "{date}", "DESC" );

  $c = $p->join( "blog_post_categories", "c" );
  $c->on( "{id}", $p->column("category") );
  $name = $q->sanitize( "mesquite" );
  $c->where( "{name}", "LIKE", $name, "AND" );
  $name = $q->sanitize( "blowfish" );
  $c->where( "{name}", "!=", $name );

  $d = $p->join( "blog_post_data", "d" );
  $d->select( "{name}, {title}, {body}" );
  $d->on( "{id}", $p->column("id") );

  $sql = "SELECT d.name, d.title, d.body FROM blog_posts AS p JOIN blog_post_categories AS c ON c.id = p.category JOIN blog_post_data AS d ON d.id = p.id WHERE c.name LIKE 'mesquite' AND c.name != 'blowfish' ORDER BY p.date DESC";
  $bench->prepare( "Query with Order, Join, On, Select, and Where" );
  $bench->assert( $q->select_sql(), $sql );

  $bench->dump();

?>