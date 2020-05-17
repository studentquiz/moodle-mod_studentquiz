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
 * An adhoc task for sending no digest notification.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\task;

use core\message\message;

defined('MOODLE_INTERNAL') || die();

/**
 * An adhoc task for sending no digest notification.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_no_digest_notification_task extends \core\task\adhoc_task {

    /**
     * Send out messages.
     */
    public function execute() {
        global $PAGE;
        $data = $this->get_custom_data();

        mtrace('Sending notification for StudentQuiz for question ' .
                $data->messagedata->questionname . ' to ' . $data->messagedata->recepientname);

        $renderer = $PAGE->get_renderer('mod_studentquiz');
        $contentdata = [
                'recipientname' => $data->messagedata->recepientname,
                'questionname' => $data->messagedata->questionname,
                'modulename' => $data->messagedata->modulename,
                'coursename' => $data->messagedata->coursename,
                'actorname' => $data->messagedata->actorname,
                'timestamp' => $data->messagedata->timestamp,
                'questionurl' => $data->messagedata->questionurl,
                'eventname' => $data->eventname
        ];
        $fullmessagehtml = $renderer->render_from_template('mod_studentquiz/single_email_notification', $contentdata);

        $eventdata = new message();
        $eventdata->component = 'mod_studentquiz';
        $eventdata->name = 'questionchanged';
        $eventdata->notification = 1;
        $eventdata->courseid = $data->courseid;
        $eventdata->userfrom = $data->submitter;
        $eventdata->userto = $data->recipient;
        $eventdata->subject = get_string('emaildigestsubject', 'mod_studentquiz');
        $eventdata->smallmessage = $fullmessagehtml;
        $eventdata->fullmessage = $fullmessagehtml;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $fullmessagehtml;
        $eventdata->contexturl = $data->questionurl;
        $eventdata->contexturlname = $data->questionname;

        return message_send($eventdata);
    }
}
