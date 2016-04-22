<?php

namespace mod_studentquiz\bank;


class difficulty_level_column extends \core_question\bank\column_base {
    public function get_name() {
        return 'difficulty_level';
    }

    protected function get_title() {
        return get_string('difficulty_level_column_name', 'studentquiz');
    }

    protected function display_content($question, $rowclasses) {
        if (!empty($question->studentquiz_difficulty_level)) {
            echo $question->studentquiz_difficulty_level;
        } else {
            echo "no difficulty level";
        }
    }

    public function get_extra_joins() {
		return array('dl' => 'LEFT JOIN (' 
			. 'SELECT IF(total = 0, 0, ROUND(1 - (correct / total), 2)) AS studentquiz_difficulty_level,'
			. 'questionid'
			. ' FROM ('
			. 'SELECT' 
			. ' COUNT(IF(rightanswer = responsesummary, 1, NULL)) AS correct,'
			. 'COUNT(IF(responsesummary IS NOT NULL, 1, NULL)) AS total,'
			. 'questionid'
			. ' FROM {question_attempts}'
			. ' GROUP BY questionid'
			. ') AS T1) dl ON dl.questionid = q.id');
    }

    public function get_required_fields() {
        return array('dl.studentquiz_difficulty_level');
    }
    public function is_sortable() {
        return 'dl.studentquiz_difficulty_level';
    }
}