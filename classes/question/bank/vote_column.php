<?php

namespace mod_studentquiz\bank;


class vote_column extends \core_question\bank\column_base {
    public function get_name() {
        return 'votes';
    }

    protected function get_title() {
        return get_string('vote_column_name', 'studentquiz');
    }

    protected function display_content($question, $rowclasses) {
        if (!empty($question->studentquiz_vote_point)) {
            echo $question->studentquiz_vote_point;
        } else {
            echo "no votes";
        }
    }

    public function get_extra_joins() {
        return array('vo' => 'LEFT JOIN ('
        .'SELECT ROUND(SUM(studentquiz_vote_point)/COUNT(studentquiz_vote_point), 2) as studentquiz_vote_point'
        .', question_id FROM {studentquiz_vote}) vo ON vo.question_id = q.id');
    }

    public function get_required_fields() {
        return array('vo.studentquiz_vote_point');
    }
    public function is_sortable() {
        return 'vo.studentquiz_vote_point';
    }
}
