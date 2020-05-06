<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task for sending digest notification.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\task;

use core\message\message;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * A scheduled task for sending digest notification.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_digest_notification_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('scheduled_task_send_digest_notification', 'mod_studentquiz');
    }

    /**
     * Send out messages.
     */
    public function execute() {
        global $DB, $USER, $PAGE;

        mtrace('Sending digest notification for StudentQuiz');
        $renderer = $PAGE->get_renderer('mod_studentquiz');
        date_default_timezone_set('UTC');

        $sql = 'SELECT DISTINCT sn.studentquizid, 1
                  FROM {studentquiz_notification} sn
                 WHERE sn.timetosend <= :timetosend
                       AND status = :status';
        $studentquizids = $DB->get_records_sql_menu($sql, ['timetosend' => strtotime(date('Y-m-d')), 'status' => 0]);

        $recordids = [];
        $messagetotal = 0;
        foreach ($studentquizids as $studentquizid => $notused) {
            $notificationqueues = $DB->get_recordset_select('studentquiz_notification',
                    'timetosend <= :timetosend AND status = :status AND studentquizid = :studentquizid',
                    ['timetosend' => strtotime(date('Y-m-d')), 'status' => 0, 'studentquizid' => $studentquizid]);
            $studentquiz = $DB->get_record('studentquiz', ['coursemodule' => $studentquizid]);

            $recipients = [];
            foreach ($notificationqueues as $notificationqueue) {
                if (!array_key_exists($notificationqueue->recipientid, $recipients)) {
                    $recipients[$notificationqueue->recipientid] = [];
                }
                $recipients[$notificationqueue->recipientid][] = unserialize($notificationqueue->content);
                $recordids[] = $notificationqueue->id;
            }
            $notificationqueues->close();

            foreach ($recipients as $userid => $datas) {
                $contentdata = [
                        'recipientname' => $datas[0]['messagedata']->recepientname,
                        'digesttype' => $studentquiz->digesttype == 1 ? 'Daily' : 'Weekly',
                        'modulename' => $studentquiz->name,
                        'activityurl' => (new moodle_url('/mod/studentquiz/view.php',
                                ['cmid' => $studentquiz->coursemodule]))->out(),
                        'notifications' => []
                ];
                $total = 0;
                foreach ($datas as $data) {
                    $total++;
                    $contentdata['notifications'][] = [
                            'seq' => $total,
                            'timestamp' => $data['messagedata']->timestamp,
                            'questionname' => $data['messagedata']->questionname,
                            'actiontype' => $data['eventname'],
                            'actorname' => $data['messagedata']->actorname
                    ];
                }
                $fullmessagehtml = $renderer->render_from_template('mod_studentquiz/digest_email_notification', $contentdata);

                $eventdata = new message();
                $eventdata->component = 'mod_studentquiz';
                $eventdata->name = 'questionchanged';
                $eventdata->notification = 1;
                $eventdata->courseid = 0;
                $eventdata->userfrom = $USER; // Was done by cron_setup_user().
                $eventdata->userto = \core_user::get_user($userid);
                $eventdata->subject = get_string('emaildigestsubject', 'mod_studentquiz');
                $eventdata->smallmessage = $fullmessagehtml;
                $eventdata->fullmessage = $fullmessagehtml;
                $eventdata->fullmessageformat = FORMAT_HTML;
                $eventdata->fullmessagehtml = $fullmessagehtml;

                message_send($eventdata);
                $messagetotal++;
                mtrace("Notification to {$datas[0]['messagedata']->recepientname} has been sent", 1);
            }
        }

        if (!empty($recordids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($recordids, SQL_PARAMS_NAMED);
            $insql = ' id ' . $insql;
            $DB->set_field_select('studentquiz_notification', 'status', 1, $insql, $inparams);
        }

        mtrace("Sent {$messagetotal} messages!");
    }
}
