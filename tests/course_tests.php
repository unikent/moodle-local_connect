<?php
/*
 * something like the following needs to find its way into phpunit.xml in the moodle root.
 *
 * <const name="TEST_CONNECT_DATABASE" value='{"driver":"mysqli","library":"native","host":"localhost","name":"connect_test","user":"root","pass":"","prefix":"","options":[]}'/>
 *
 */

defined('MOODLE_INTERNAL') || die();

class kent_course_tests extends advanced_testcase {

  public function test_disengage() {
    // given a created in moodle course
    global $CONNECTDB;
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,module_code) values (?,?,?,?,?,?)';
    $CONNECTDB->execute($insert,
      array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456', 'session_code' => '2013', 'chksum' => '0987565',
      'parent_id' => null, 'module_code' => '123'));

    // then we expect it to be scheduled
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->once())
      ->method('send')->with(
        $this->equalTo('connect.job.disengage_course'),
        $this->equalTo('0987565'));

    // when we disengage it
    $result = \local_connect\course::disengage_all(array('0987565'));

    // and we expect an empty return
    $this->assertEmpty($result);

    $STOMP = $origstomp;
  }

  public function test_schedule_non_unique_module_code() {
    // given a couple of courses, one created and with a module code
    global $CONNECTDB;

    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,module_code) values (?,?,?,?,?,?)';
    $CONNECTDB->execute($insert,
      array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456', 'session_code' => '2013', 'chksum' => '0987565',
      'parent_id' => null, 'module_code' => '123'));
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '1234567',
      'session_code' => '2013', 'chksum' => '09875656', 'parent_id' => null, 'module_code' => null));

    // when we attempt a schedule of the second
    $input = array(
      array(
        'id' => '09875656',
        'code' => '123',
        'title' => '',
        'synopsis' => '',
        'category' => '2'
      )
    );
    $result = \local_connect\course::schedule_all($input);

    // then we expect it to fail due to the non unique code
    $this->assertContains(array('error_code'=>'duplicate','id'=>'09875656'),$result);
  }

  public function test_schedule() {
    global $CONNECTDB;

    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id) values (?,?,?,?,?)';

    // given a course
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '0987565', 'parent_id' => null));

    $input = array(
      array(
        'id' => '0987565',
        'code' => 'SS803 (2013/2014)',
        'title' => 'Research Methods (2013/2014)',
        'synopsis' => 'something synopsisy',
        'category' => '2'
      )
    );

    // and a mocked queue provider
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->once())
      ->method('send')->with(
        $this->equalTo('connect.job.create_course'),
        $this->equalTo('0987565'));

    // when we attempt to schedule it
    $result = \local_connect\course::schedule_all($input);

    // then we expect an empty return
    $this->assertEmpty($result);

    // and its details to have been updated
    $course = $CONNECTDB->get_record('courses',array('chksum'=>'0987565'));

    $this->assertEquals('SS803 (2013/2014)', $course->module_code);
    $this->assertEquals('Research Methods (2013/2014)', $course->module_title);
    $this->assertEquals('something synopsisy', $course->synopsis);
    $this->assertEquals('2', $course->category_id);
    $this->assertEquals(
      \local_connect\course::$states['scheduled'],
      \local_connect\course::$states['scheduled'] & $course->state);

    $STOMP = $origstomp;
  }

  public function test_fetch_all_courses_works_with_nested() {
    global $CONNECTDB;

    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id) values (?,?,?,?,?)';

    // given a course
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '0987565', 'parent_id' => null));

    // with children
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => 'child',
      'session_code' => '2013', 'chksum' => 'child0987565', 'parent_id' => '123456'));
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => 'child2',
      'session_code' => '2013', 'chksum' => 'child20987565', 'parent_id' => '123456'));

    // when we retrieve all courses
    $courses = \local_connect\course::get_courses(array(),false);

    // then children should be nested
    $this->assertContains('child0987565',$courses['0987565']->children);
    $this->assertContains('child20987565',$courses['0987565']->children);
  }

  public function setUp() {
    global $STOMP;
    $this->queues = array();
    $STOMP = new stdClass();
    $self = $this;
    $STOMP->send = function ($q, $m) use (&$self) {
      if (!isset($self->queues[$q])) {
        $self->queues[$q] = array();
      }
      $self->queues[$q] []= $m;
    };

    global $CFG;
    $CFG->connect = (object)array('db' => json_decode(TEST_CONNECT_DATABASE,true));

    global $CONNECTDB;
    $CONNECTDB->execute('truncate table courses');
    $this->tr = $CONNECTDB->start_delegated_transaction();
  }

  public function tearDown() {
    // stop it bubbling
    try { $this->tr->rollback(new Exception()); } catch (Exception $ex) {}

    global $CFG;
    unset($CFG->connect);
  }

}
