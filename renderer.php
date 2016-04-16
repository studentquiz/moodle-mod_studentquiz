<?php

defined('MOODLE_INTERNAL') || die();

class mod_studentquiz_renderer extends plugin_renderer_base {

    public function summary_table($sessionid) {
        global $DB;

        $session = $DB->get_record('studentquiz_practice_session', array('id' => $sessionid));
        $table = new html_table();
        $table->attributes['class'] = 'generaltable qpracticesummaryofattempt boxaligncenter';
        $table->caption = get_string('practice_past_sessions', 'studentquiz');
        $table->head = array(get_string('practice_total_questions', 'studentquiz'), get_string('practice_total_marks', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();
        $table->data[] = array($session->total_no_of_questions, $session-> 	marks_obtained . '/' . $session->total_marks);
        echo html_writer::table($table);
    }

    public function summary_form($sessionid) {

        $actionurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $sessionid));
        $output = '';
        $output .= html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
            'enctype' => 'multipart/form-data', 'id' => 'responseform'));
        $output .= html_writer::start_tag('div', array('align' => 'center'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'back', 'value' => get_string('practice_resume', 'studentquiz')));
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'finish', 'value' => get_string('practice_submit_finish', 'studentquiz')));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        echo $output;
    }

    public function report_table($cm, $context) {
        global $DB, $USER;

        $canviewallreports = true; //has_capability('mod/studentquiz:viewallreports', $context);
        $canviewmyreports = true; //has_capability('mod/studentquiz:viewmyreport', $context);

        if ($canviewmyreports) {
            $session = $DB->get_records('studentquiz_practice_session', array('studentquiz_id' => $cm->instance, 'user_id' => $USER->id));
        } if ($canviewallreports) {
            $session = $DB->get_records('studentquiz_practice_session', array('studentquiz_id' => $cm->instance));
        }

        if ($session != null) {
            $table = new html_table();
            $table->attributes['class'] = 'generaltable qpracticesummaryofpractices boxaligncenter';
            $table->caption = get_string('practice_past_sessions', 'studentquiz');
            $table->head = array(get_string('practice_date', 'studentquiz'), get_string('practice_category', 'studentquiz'),
                get_string('score', 'studentquiz'),
                get_string('pracitce_no_of_questions_viewed', 'studentquiz'),
                get_string('practice_no_of_questions_right', 'studentquiz'));
            $table->align = array('left', 'left', 'left', 'left', 'left', 'left', 'left');
            $table->size = array('', '', '', '', '', '', '', '');
            $table->data = array();
            foreach ($session as $practice) {
                $date = $practice->practice_date;
                $categoryid = $practice->category_category_id;

                $category = $DB->get_records_menu('question_categories', array('id' => $categoryid), 'name');
                /* If the category has been deleted, jump to the next session */
                if (empty($category)) {
                    continue;
                }
                $table->data[] = array(userdate($date), $category[$categoryid],
                    $practice->marks_obtained . '/' . $practice->total_marks,
                    $practice->total_no_of_questions, $practice->total_no_of_questions_right);
            }
            echo html_writer::table($table);
        } else {
            $viewurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));
            $viewtext = get_string('practice_no_records_viewurl', 'studentquiz');
            redirect($viewurl, $viewtext);
        }
    }

}
