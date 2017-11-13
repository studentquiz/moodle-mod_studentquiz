<?php

// @TODO Reduce filelib if not necessary.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

$attemptid = required_param('id', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));

$cm = get_coursemodule_from_instance('studentquiz', $attempt->studentquizid);
$course = $DB->get_record('course', array('id' => $cm->course));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO: Manage capabilities and events for studentquiz
$questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);
//$behavior = $questionusage->get_preferred_behaviour();
//$questionusage->get_question_attempt($slot)->
//$a = $questionusage->get_question_attempt($slot)->get_behaviour()->can_finish_during_attempt();

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $attemptid, 'slot' => $slot));
$stopurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $attemptid));

// Get Current Question.
$question = $questionusage->get_question($slot);
// Navigatable?
$hasnext = $slot < $questionusage->question_count();
$hasprevious = $slot > $questionusage->get_first_question_number();
$canfinish = $questionusage->can_question_finish_during_attempt($slot);



if (data_submitted()) {
    if (optional_param('next', null, PARAM_BOOL)) {
        // There is submitted data. Process it.
        $transaction = $DB->start_delegated_transaction();

        $questionusage->finish_question($slot);

        // @TODO: Update tracking data --> studentquiz progress, studentquiz_attempt
        $transaction->allow_commit();

        if ($hasnext) {
            $actionurl = new moodle_url($actionurl, array('slot' => $slot + 1));
            redirect($actionurl);
        } else {
            redirect($stopurl);
        }
    } else if (optional_param('previous', null, PARAM_BOOL)) {
        if ($hasprevious) {
            $actionurl = new moodle_url($actionurl, array('slot' => $slot - 1));
            redirect($actionurl);
        } else {
            $actionurl = new moodle_url($actionurl, array('slot' => $questionusage->get_first_question_number()));
            redirect($actionurl);
        }
    } else if (optional_param('finish', null, PARAM_BOOL)) {
        question_engine::save_questions_usage_by_activity($questionusage);
        //@TODO Trigger events?
        redirect($stopurl);
    } else {
        $questionusage->process_all_actions();
        question_engine::save_questions_usage_by_activity($questionusage);
        redirect($actionurl);
    }
}

// hast answered?
$hasanswered = false;
switch($questionusage->get_question_attempt($slot)->get_state()) {
    case question_state::$gradedpartial:
    case question_state::$gradedright:
    case question_state::$gradedwrong:
    case question_state::$complete:
        $hasanswered = true;
        break;
    case question_state::$todo:
    default:
        $hasanswered = false;
}
// is voted?
$hasvoted = false;

$options = new question_display_options();
// TODO do they do anything? $headtags not used anywhere and question_engin..._js returns void
$headtags = '';
$headtags .= $questionusage->render_question_head_html($slot);
$headtags .= question_engine::initialise_js();

/** @var mod_studentquiz_renderer $output */
$output = $PAGE->get_renderer('mod_studentquiz');
// Start output.
$PAGE->set_url($actionurl);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'initialise');
$title = format_string($question->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context($context);
echo $OUTPUT->header();


// Start the question form.

$html = html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));

// Output the question.
// TODO, options?
$html .= $questionusage->render_question($slot, $options, (string)$slot);

// Output the voting.
if ($hasanswered) {
    $html .= $output->feedback($question, $options);
}

// Finish the question form.
$html .= html_writer::start_tag('div', array('class' => 'row'));
$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'pull-left'));
if ($hasprevious) {
    $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'previous', 'value' => 'Previous', 'class' => 'btn btn-primary'));
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');

$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'mdl-align'));
if ($canfinish && ($hasnext || !$hasanswered)) {
    $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'finish', 'value' => 'Finish', 'class' => 'btn btn-link'));
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'pull-right'));
// @TODO: extract to language file.
if ($hasanswered /*&& $voted*/) {
    if ($hasnext) {
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'next', 'value' => 'Next', 'class' => 'btn btn-primary'));
    } else { // Finish instead of next on the last question.
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'finish', 'value' => 'Finish', 'class' => 'btn btn-primary'));
    }
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('form');

echo $html;

// Display the settings form.

echo $OUTPUT->footer();

