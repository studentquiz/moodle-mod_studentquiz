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
        if (!empty($question->vote)) {
            echo $question->vote;
        } else {
            echo "no votes";
        }
    }

    public function get_extra_joins() {
        return array('vo' => 'LEFT JOIN ('
        .'SELECT ROUND(SUM(vote)/COUNT(vote), 2) as vote'
        .', questionid FROM {studentquiz_vote} GROUP BY questionid) vo ON vo.questionid = q.id');
    }

    public function get_required_fields() {
        return array('vo.vote');
    }
    public function is_sortable() {
        return 'vo.vote';
    }
}
