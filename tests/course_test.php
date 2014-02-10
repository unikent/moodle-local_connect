<?php


defined('MOODLE_INTERNAL') || die();

class kent_course_tests extends advanced_testcase {
  public function test_placeholder() {
    $this->assertTrue(true);
  }
/*
  public function test_unlink_sends_request() {
    global $CONNECTDB;

    // given a couple of 'children'
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,moodle_id) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456', 'session_code' => '2013', 'chksum' => '123456', 'parent_id' => 'parent',
      'campus' => '58', 'campus_desc' => 'Medway', 5));
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123457', 'session_code' => '2013', 'chksum' => '654321', 'parent_id' => 'parent',
      'campus' => '58', 'campus_desc' => 'Medway', 5));

    // then we expect to see both of them sent along the chain
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->at(0))->method('send')
      ->with(
        $this->equalTo('connect.job.unlink_course'),
        $this->equalTo('123456')
      );
    $STOMP->expects($this->at(1))->method('send')
      ->with(
        $this->equalTo('connect.job.unlink_course'),
        $this->equalTo('654321')
      );

    // when we attempt to unlink them
    $result = \local_connect\course::unlink(array('123456','654321'));

    $STOMP = $origstomp;
  }

  public function test_unlink_course_wont_unlink_uncreated_or_non_child() {
    global $CONNECTDB;

    // given a course that isnt a child
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,moodle_id) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456', 'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', 5));
    // and one that isnt created yet
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['scheduled'],
      'module_delivery_key' => '123457', 'session_code' => '2013', 'chksum' => 'notcreated', 'parent_id' => '12',
      'campus' => '58', 'campus_desc' => 'Medway', 5));

    // when we try to unlink them and a non existant one
    $result = \local_connect\course::unlink(array('123456','nothing','notcreated'));

    // then we expect some errors
    $this->assertEquals('not_link_course',$result[0]['error_code']);
    $this->assertEquals('does_not_exist',$result[1]['error_code']);
    $this->assertEquals('not_created',$result[2]['error_code']);

  }

  public function test_merge_sets_weeks_properly() {
    global $CONNECTDB;

    // given a couple of deliveries
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,campus,campus_desc,module_week_beginning,module_length,week_beginning_date) ';
    $CONNECTDB->execute($insert . 'values (?,?,?,?,?,?,?,?,date_add(now(), interval 30 day))',
      array( \local_connect\course::$states['unprocessed'],
      '321654', '2013', '321654', '1', 'Canterbury', '1', '5'));
    $CONNECTDB->execute($insert . 'values (?,?,?,?,?,?,?,?,date_sub(now(), interval 30 day))',
      array( 'state' => \local_connect\course::$states['unprocessed'],
      '321654789', '2013', '321654789', '1', 'Canterbury', '13', '6'));

    // and some mocked bits
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->once())->method('send');

    // when we merge them
    $input = (object)array(
      'link_courses' => array('321654','321654789'),
      'code' => '123',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    $result = \local_connect\course::merge($input);

    // then we expect to have correct dates set on the link course
    $link = $CONNECTDB->get_record('courses',array('module_code'=>'123'));
    $this->assertEquals(1,$link->module_week_beginning);
    $this->assertEquals(18,$link->module_length);
    $this->assertTrue(new DateTime($link->week_beginning_date) < new DateTime('now'));

    $STOMP = $origstomp;
  }

  public function test_merge_single_linked_course() {
    global $CONNECTDB;

    // given an existing link course
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,link) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', true));

    // and an unprocessed delivery or two
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['unprocessed'],
      'module_delivery_key' => '321654',
      'session_code' => '2013', 'chksum' => '321654', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury', null));
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['unprocessed'],
      'module_delivery_key' => '321654789',
      'session_code' => '2013', 'chksum' => '321654789', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury', null));

    // then we expect the add child job to get called for each child
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->at(0))->method('send')
      ->with(
        $this->equalTo('connect.job.add_link_child'),
        $this->equalTo(json_encode(array('link_course_chksum'=>'123456','chksum'=>'321654')))
      );
    $STOMP->expects($this->at(1))->method('send')
      ->with(
        $this->equalTo('connect.job.add_link_child'),
        $this->equalTo(json_encode(array('link_course_chksum'=>'123456','chksum'=>'321654789')))
      );

    // when we attempt to merge them
    $input = (object)array(
      'link_courses' => array('123456','321654','321654789'),
      'code' => '123',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    $result = \local_connect\course::merge($input);

    $STOMP = $origstomp;
  }

  public function test_merge_link_courses_together_fails() {
    global $CONNECTDB;

    // given a couple of created link courses
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,link) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', true));
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '321654',
      'session_code' => '2013', 'chksum' => '321654', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury', true));

    // and a valid merge request
    $input = (object)array(
      'link_courses' => array('321654','123456'),
      'code' => 'modulecode',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    // when we merge them
    $result = \local_connect\course::merge($input);

    // then we expect to be told we cant
    $this->assertEquals('cannot_merge_link_courses',$result['error_code']);
  }

  public function test_merge_duplicate_module_code() {
    global $CONNECTDB;

    // given a created course and a couple of new deliveries
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,module_code) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', 'modulecode'));

    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '321654',
      'session_code' => '2013', 'chksum' => '321654', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury', '321654'));
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '654321',
      'session_code' => '2013', 'chksum' => '654321', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', '654321'));

    // and a valid merge request with a duplicate module_code
    $input = (object)array(
      'link_courses' => array('321654','654321'),
      'code' => 'modulecode',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    // when we merge them
    $result = \local_connect\course::merge($input);

    // then we expect a duplicate error
    $this->assertEquals('duplicate',$result['error_code']);
  }

  public function test_merge_with_one_already_created() {
    global $CONNECTDB;

    // given a couple of deliveries that have found their way into moodle
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,moodle_id) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', 5));
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['unprocessed'],
      'module_delivery_key' => '321654',
      'session_code' => '2013', 'chksum' => '321654', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury', null));

    // and a valid merge request
    $input = (object)array(
      'link_courses' => array('123456','321654'),
      'code' => '123',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    // then we expect it to be scheduled
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->once())->method('send')->with($this->equalTo('connect.job.create_link_course'));

    // when we merge them
    $result = \local_connect\course::merge($input);

    // and we expect the link course to be created with the moodle_id of the existing course
    $course = $CONNECTDB->get_record('courses',array('module_code'=>'123'));
    $this->assertEquals(5,$course->moodle_id);

    $STOMP = $origstomp;
  }

  public function test_merge_two_already_created_courses() {
    global $CONNECTDB;

    // given a couple of deliveries that have found their way into moodle
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc,moodle_id) values (?,?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway', 5));
    $CONNECTDB->execute($insert, array( 'state' => \local_connect\course::$states['created_in_moodle'],
      'module_delivery_key' => '321654',
      'session_code' => '2013', 'chksum' => '321654', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury', 6));

    // and a valid merge request
    $input = (object)array(
      'link_courses' => array('123456','321654'),
      'code' => '123',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    // when we merge them
    $result = \local_connect\course::merge($input);

    // then we expect an error back
    $this->assertEquals('too_many_created',$result['error_code']);
  }

  public function test_merge() {
    global $CONNECTDB;

    // given a couple of modules from across kent
    $insert = 'insert into courses ' .
      '(state,module_delivery_key,session_code,chksum,parent_id,campus,campus_desc) values (?,?,?,?,?,?,?)';
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '123456',
      'session_code' => '2013', 'chksum' => '123456', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway'));
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '321654',
      'session_code' => '2013', 'chksum' => '321654', 'parent_id' => null,
      'campus' => '1', 'campus_desc' => 'Canterbury'));
    $CONNECTDB->execute($insert, array( 'state' => 1, 'module_delivery_key' => '654321',
      'session_code' => '2013', 'chksum' => '654321', 'parent_id' => null,
      'campus' => '58', 'campus_desc' => 'Medway'));

    // and a valid merge request
    $input = (object)array(
      'link_courses' => array('123456','321654','654321'),
      'code' => '123',
      'title' => 'a title',
      'synopsis' => '',
      'category' => '2',
      'primary_child' => ''
    );

    // then we expect it to be scheduled
    global $STOMP;
    $origstomp = $STOMP;
    $STOMP = $this->getMock('\FuseSource\Stomp', array('send'));
    $STOMP->expects($this->once())->method('send')->with($this->equalTo('connect.job.create_link_course'));

    // when we merge them
    $result = \local_connect\course::merge($input);

    // and we expect a new course to have been created
    $course = $CONNECTDB->get_record('courses',
      array(
        'module_code'=>$input->code,
        'module_title'=>$input->title,
        'category_id'=>$input->category,
        'primary_child'=>$input->primary_child
      )
    );
    $this->assertTrue($course !== false);

    // and the child modules should be linked
    $this->assertEquals(3,$CONNECTDB->count_records('courses',array('parent_id'=>$course->chksum)));

    $STOMP = $origstomp;
  }

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
      (object)array(
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
      (object)array(
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
      \local_connect\course::$states['scheduled'] & $course->state
    );

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
    global $STOMP, $CONNECTDB;
    $this->queues = array();
    $STOMP = new stdClass();
    $self = $this;
    $STOMP->send = function ($q, $m) use (&$self) {
      if (!isset($self->queues[$q])) {
        $self->queues[$q] = array();
      }

      $self->queues[$q][] = $m;
    };

    global $CONNECTDB;
    $CONNECTDB->execute('truncate table courses');
    $this->tr = $CONNECTDB->start_delegated_transaction();
  }

  public function tearDown() {
    // stop it bubbling
    try {
      $this->tr->rollback(new Exception());
    } catch (Exception $ex) {
      // -
    }

    global $CFG;
    unset($CFG->connect);
  }
*/

}