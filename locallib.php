<?php

/**
 * This function has been copied from /lib/enrollib.php from enrol_get_enrolment_end and added in role.
 */
function kent_is_user_enrolled_as_role($courseid, $userid, $roleid) {
    global $DB;

    $context = context_course::instance($courseid, MUST_EXIST);

    $sql = "SELECT ue.*
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
              JOIN {user} u ON u.id = ue.userid
              JOIN {role_assignments} ra ON ra.userid = u.id AND contextid = :contextid
             WHERE ue.userid = :userid AND ue.status = :active AND e.status = :enabled AND u.deleted = 0 AND ra.roleid = :roleid";
    $params = array('enabled'=>ENROL_INSTANCE_ENABLED, 'active'=>ENROL_USER_ACTIVE, 'userid'=>$userid, 'courseid'=>$courseid, 'roleid'=>$roleid, 'contextid'=>$context->id);

    if (!$enrolments = $DB->get_records_sql($sql, $params)) {
        return false;
    }

    //TODO - FG30 - I don't think we actually need all of this below, as its mainly the above bit which checks if the user is enrolled.
    //Test at earliest opportunity and get rid if not needed

    $changes = array();

    foreach ($enrolments as $ue) {
        $start = (int)$ue->timestart;
        $end = (int)$ue->timeend;
        if ($end != 0 and $end < $start) {
            debugging('Invalid enrolment start or end in user_enrolment id:'.$ue->id);
            continue;
        }
        if (isset($changes[$start])) {
            $changes[$start] = $changes[$start] + 1;
        } else {
            $changes[$start] = 1;
        }
        if ($end === 0) {
            // no end
        } else if (isset($changes[$end])) {
            $changes[$end] = $changes[$end] - 1;
        } else {
            $changes[$end] = -1;
        }
    }

    // let's sort then enrolment starts&ends and go through them chronologically,
    // looking for current status and the next future end of enrolment
    ksort($changes);

    $now = time();
    $current = 0;
    $present = null;

    foreach ($changes as $time => $change) {
        if ($time > $now) {
            if ($present === null) {
                // we have just went past current time
                $present = $current;
                if ($present < 1) {
                    // no enrolment active
                    return false;
                }
            }
            if ($present !== null) {
                // we are already in the future - look for possible end
                if ($current + $change < 1) {
                    return $time;
                }
            }
        }
        $current += $change;
    }

    if ($current > 0) {
        return 0;
    } else {
        return false;
    }
}