<?php
/**
 * This view renders a single question during the executing of a StudentQuiz
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('id', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));

$cm = get_coursemodule_from_instance('studentquiz', $attempt->studentquizid);
$cmid = $cm->id;
$course = $DB->get_record('course', array('id' => $cm->course));

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
$studentquiz = mod_studentquiz_load_studentquiz($cmid, $context->id);

global $USER;
$userid = $USER->id;

// TODO: Manage capabilities and events for studentquiz.
$questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);
/*
 $behavior = $questionusage->get_preferred_behaviour().
 $questionusage->get_question_attempt($slot)->
 $a = $questionusage->get_question_attempt($slot)->get_behaviour()->can_finish_during_attempt();
*/

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('cmid' => $cmid, 'id' => $attemptid, 'slot' => $slot));
$stopurl = new moodle_url('/mod/studentquiz/summary.php', array('cmid' => $cmid, 'id' => $attemptid));

// Get Current Question.
$question = $questionusage->get_question($slot);
// Navigatable?
$questionscount = $questionusage->question_count();
$hasnext = $slot < $questionscount;
$hasprevious = $slot > $questionusage->get_first_question_number();
$canfinish = $questionusage->can_question_finish_during_attempt($slot);



if (data_submitted()) {
    if (optional_param('next', null, PARAM_BOOL)) {
        // There is submitted data. Process it.
        $transaction = $DB->start_delegated_transaction();

        $questionusage->finish_question($slot);

        // TODO: Update tracking data --> studentquiz progress, studentquiz_attempt.
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
        // TODO Trigger events?
        redirect($stopurl);
    } else {
        $questionusage->process_all_actions();
        question_engine::save_questions_usage_by_activity($questionusage);
        redirect($actionurl);
    }
}

// Hast answered?
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
// Is rated?
$hasrated = false;

$options = new question_display_options();
// TODO do they do anything? $headtags not used anywhere and question_engin..._js returns void.
$headtags = '';
$headtags .= $questionusage->render_question_head_html($slot);
$headtags .= question_engine::initialise_js();

/** @var mod_studentquiz_renderer $output */
$output = $PAGE->get_renderer('mod_studentquiz', 'attempt');
// Start output.
$PAGE->set_url($actionurl);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'initialise');
$title = format_string($question->name);
$PAGE->set_title($cm->name);
$PAGE->set_heading($cm->name);
$PAGE->set_context($context);
echo $OUTPUT->header();




$info = new stdClass();
$info->total = $questionscount;
$info->group = $slot;
$info->one = 0;
$texttotal = $questionscount . ' ' . get_string('questions', 'studentquiz');
$html = '';

$html .= html_writer::div($output->render_progress_bar($info, $texttotal), '', array('title' => $texttotal));

// Render the question title
$html .= html_writer::tag('h2', $title);

// Start the question form.

$html .= html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));

$html .= '<input type="hidden" class="cmid_field" name="cmid" value="' . $cmid . '" />';

// Output the question.
// TODO, options?
$html .= $questionusage->render_question($slot, $options, (string)$slot);

// Output the rating.
if ($hasanswered) {
    $comments = mod_studentquiz_get_comments_with_creators($question->id);

    $anonymize = $studentquiz->anonymrank;
    if(has_capability('mod/studentquiz:unhideanonymous', $context)) {
        $anonymize = false;
    }
    $ismoderator = false;
    if(mod_studentquiz_check_created_permission($cmid)) {
        $ismoderator = true;
    }

    $html .= $output->feedback($question, $options, $cmid, $comments, $userid, $anonymize, $ismoderator);
}

// Finish the question form.
$html .= html_writer::start_tag('div', array('class' => 'row'));
$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'pull-left'));
if ($hasprevious) {
    $html .= html_writer::empty_tag('input',
        array('type' => 'submit', 'name' => 'previous', 'value' =>  get_string('previous_button', 'studentquiz'), 'class' => 'btn btn-primary'));
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');

$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'mdl-align'));

if ($canfinish && ($hasnext || !$hasanswered)) {
    $html .= html_writer::empty_tag('input',
        array('type' => 'submit', 'name' => 'finish', 'value' =>  get_string('finish_button', 'studentquiz'), 'class' => 'btn btn-link'));
}

$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'pull-right'));
if ($hasanswered) { // And ~$hasrated, but done using javascript as not showing the next button seems not intuitive
    if ($hasnext) {
        $html .= html_writer::empty_tag('input',
            array('type' => 'submit', 'name' => 'next', 'value' =>  get_string('next_button', 'studentquiz'), 'class' => 'btn btn-primary'));
    } else { // Finish instead of next on the last question.
        $html .= html_writer::empty_tag('input',
            array('type' => 'submit', 'name' => 'finish', 'value' => get_string('finish_button', 'studentquiz'), 'class' => 'btn btn-primary'));
    }
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('form');


echo $html;

// Display the settings form.

echo $OUTPUT->footer();

