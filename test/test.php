<?php include "conf-test.php"; /* load a local configuration */ ?>
<?php global $db_conn; ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
$db_conn->query('drop table if exists __system__');
$db_conn->query('drop table if exists __tmp__b');
$db_conn->query('drop table if exists __tmp__');
$db_conn->query('drop table if exists test1');
$db_conn->query('drop table if exists test2_b');
$db_conn->query('drop table if exists test2');
$db_conn->query('drop table if exists test4_b');
$db_conn->query('drop table if exists test4');
$db_conn->query('drop table if exists test3_b');
$db_conn->query('drop table if exists test3');

db_system_init();

class DBMYSQL extends PHPUnit_Framework_TestCase {
  protected $backupGlobals = FALSE;

  public function testInit () {
    global $db;

    $system = new DB_System($db);
  }

  public function test1 () {
    $table = new DB_Table(null, null);
    
    $table->save(array(
      'id' => 'test1',
      'fields' => array(
        'a' => array(
          'type' => 'text',
          'count' => false,
        ),
        'b' => array(
          'type' => 'text',
          'count' => false,
        ),
      ),
    ));

    $entry = new DB_Entry('test1', null);
    $entry->save(array('a' => 'foo', 'b' => 'bar'));
    $entry = new DB_Entry('test1', null);
    $entry->save(array('a' => 'bar', 'b' => 'foo'));

    $entry = $table->get_entry(1);
    $this->assertEquals(array('a' => 'foo', 'b' => 'bar', 'id' => 1), $entry->data());
    $entry = $table->get_entry(2);
    $this->assertEquals(array('a' => 'bar', 'b' => 'foo', 'id' => 2), $entry->data());

    $entry = $table->get_entry(3);
    $this->assertEquals(null, $entry);
  }

  public function test2 () {
    $table = new DB_Table(null, null);
    
    $table->save(array(
      'id' => 'test2',
      'fields' => array(
        'a' => array(
          'type' => 'text',
          'count' => false,
        ),
        'b' => array(
          'type' => 'text',
          'count' => true,
        ),
      ),
    ));

    $entry = new DB_Entry('test2', null);
    $entry->save(array('a' => 'foo', 'b' => array('1', '2', '3')));

    $entry = new DB_Entry('test2', null);
    $entry->save(array('a' => 'bar', 'b' => array('3', '2', '1')));

    $entry = $table->get_entry(1);
    $this->assertEquals(array('a' => 'foo', 'b' => array('1', '2', '3'), 'id' => 1), $entry->data());

    $entry = $table->get_entry(2);
    $this->assertEquals(array('a' => 'bar', 'b' => array('3', '2', '1'), 'id' => 2), $entry->data());

    $entry = $table->get_entry(3);
    $this->assertEquals(null, $entry);
  }

  public function test3 () {
    $table = new DB_Table(null, null);

    $table->save(array(
      'id' => 'test3',
      'fields' => array(
        'id' => array(
          'type' => 'text',
          'count' => false,
        ),
        'a' => array(
          'type' => 'text',
          'count' => false,
        ),
        'b' => array(
          'type' => 'text',
          'count' => true,
        ),
      ),
    ));

    $entry = new DB_Entry('test3', null);
    $entry->save(array('id' => 'foo', 'a' => 'foo', 'b' => array('1', '2', '3')));

    $entry = new DB_Entry('test3', null);
    $entry->save(array('id' => 'bar', 'a' => 'bar', 'b' => array('3', '2', '1')));

    $entry = $table->get_entry('foo');
    $this->assertEquals(array('id' => 'foo', 'a' => 'foo', 'b' => array('1', '2', '3')), $entry->data());

    $entry = $table->get_entry('bar');
    $this->assertEquals(array('id' => 'bar', 'a' => 'bar', 'b' => array('3', '2', '1')), $entry->data());

    $entry = $table->get_entry('xyz');
    $this->assertEquals(null, $entry);
  }

  public function test4 () {
    $table = new DB_Table(null, null);

    $table->save(array(
      'id' => 'test4',
      'fields' => array(
        'a' => array(
          'type' => 'text',
          'count' => false,
        ),
        'b' => array(
          'type' => 'select',
          'reference' => 'test3',
          'count' => false,
        ),
      ),
    ));

    $entry = new DB_Entry('test4', null);
    $entry->save(array('a' => 'foo', 'b' => 'foo'));

    $entry = new DB_Entry('test4', null);
    $entry->save(array('a' => 'bar', 'b' => 'bar'));

    $entry = $table->get_entry(1);
    $this->assertEquals(array('a' => 'foo', 'b' => 'foo', 'id' => 1), $entry->data());

    $entry = $table->get_entry(2);
    $this->assertEquals(array('a' => 'bar', 'b' => 'bar', 'id' => 2), $entry->data());

    $entry = $table->get_entry(3);
    $this->assertEquals(null, $entry);
  }

  public function test3_back () {
    global $db_table_cache;
    global $db_table_cache_complete;
    $db_table_cache = array();
    $db_table_cache_complete = false;

    $table = get_db_table('test3');
    print_r($table);

    $data = $table->data();
    $data['fields']['b']['old_key'] = 'b';
    $data['fields']['back'] = array(
      'type' => 'text',
      'backreference' => 'test4:b',
    );

    $table->save($data);

    $entry = $table->get_entry('foo');
    print_r($entry->data());
    $this->assertEquals(array('a' => 'foo', 'b' => 'foo', 'id' => 1), $entry->data());

    $entry = $table->get_entry(2);
    $this->assertEquals(array('a' => 'bar', 'b' => 'bar', 'id' => 2), $entry->data());

    $entry = $table->get_entry(3);
    $this->assertEquals(null, $entry);
  }
}
