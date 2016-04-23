<?php

namespace mod_studentquiz\bank;


class tag_column extends \core_question\bank\column_base {
    public function get_name() {
        return 'tags';
    }

    protected function get_title() {
        return get_string('tag_column_name', 'studentquiz');
    }

    protected function display_content($question, $rowclasses) {
        if (!empty($question->tagname)) {
            echo $question->tagname;
        } else {
            echo "no tags";
        }
    }

    public function get_extra_joins() {
        return array();
    }

}
