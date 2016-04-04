<?php

namespace mod_studentquiz\bank;


class vote_column extends \core_question\bank\column_base {
    public function get_name() {
        return 'votes';
    }

    protected function get_title() {
        return get_string('vote', 'studentquiz');
    }

    protected function display_content($question, $rowclasses) {
        if (!empty($question->creatorfirstname) && !empty($question->creatorlastname)) {
            $u = new \stdClass();
            $u = username_load_fields_from_object($u, $question, 'creator');
            $date = userdate($question->timecreated, get_string('strftimedatetime', 'langconfig'));
            echo fullname($u) . '<br>' . \html_writer::tag('span', $date, array('class' => 'date'));
        }
    }

    public function get_extra_joins() {
        return array('vo' => 'JOIN {vote} vo ON vo.question_id = q.id');
    }

    public function is_sortable() {
        return array(
            'vote_points' => array('field' => 'vo.vote_points', 'title' => get_string('vote_points', 'studentquiz')),
        );
    }
}
