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
 * German strings for StudentQuiz
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'StudentQuiz';
$string['modulenameplural'] = 'StudentQuizzes';
$string['modulename_help'] = 'Die StudentQuiz-Aktivität ermöglicht es Studenten Fragen zum Pool hinzuzufügen. In der StudentQuiz-Übersicht können die Teilnehmer Fragen filtern. Sie können die gefilterten Fragen anschliessend zum üben benutzen. Der Lehrer hat die Option die Spalte "erstellt von" zu anonymisieren.<br><br>Die StudentQuiz-Aktivität vergibt den Schülern Punkte um sie zu motivieren, Fragen hinzuzufügen und damit zu üben. Die Punkte werden in einer Rangliste aufgelistet.<br><br>Weitere Informationen finden Sie hier <a href="https://github.com/frankkoch/moodle-mod_studentquiz/blob/master/manuals/User-Manual.pdf">Benutzerhandbuch</a>.';
$string['studentquizfieldset'] = 'Benutzerdefiniertes Fieldset-Beispiel';
$string['studentquizname'] = 'StudentQuiz Name';
$string['studentquizname_help'] = 'StudentQuiz Name';
$string['anonymous_checkbox_label'] = 'Student anonymisieren';
$string['quiz_advanced_settings_header'] = 'Erweiterte Einstellungen';
$string['quizpracticebehaviour'] = 'Bewertung und Kommentar';
$string['quizpracticebehaviourhelp'] = 'Bewertung und Kommentar der Fragen';
$string['quizpracticebehaviourhelp_help'] = 'Bewertung und Kommentar der Fragen';

$string['studentquiz'] = 'studentquiz';
$string['pluginadministration'] = 'StudentQuiz Administration';
$string['pluginname'] = 'StudentQuiz';
$string['vote_column_name'] = 'Bewertungen';
$string['practice_column_name'] = 'Durchführungen';
$string['comment_column_name'] = 'Kommentare';
$string['difficulty_level_column_name'] = 'Schwierigkeit';
$string['approved_column_name'] = 'Bestätigt';
$string['vote_points'] = 'Punkte';
$string['tag_column_name'] = 'Tags';
$string['start_quiz_button'] = 'Quiz starten';
$string['nav_question_and_quiz'] = 'Quiz und Fragen';
$string['nav_report'] = 'Bericht';
$string['nav_report_quiz'] = 'Quiz';
$string['nav_report_rank'] = 'Rang';
$string['nav_export'] = 'Export';
$string['nav_import'] = 'Import';
$string['nav_questionbank'] = 'Fragebank';
$string['anonymrankhelp'] = 'Anonymisieren';
$string['anonymrankhelp_help'] = 'Die Spalte "erstellt von" für die Studenten in der Frageübersicht und die Namen in der Rangliste anonymisieren.';
$string['createnewquestionfirst'] = 'Erste Frage erstellen';
$string['createnewquestion'] = 'Neue Frage erstellen';
$string['createnewquizfromfilter'] = 'Gefilterte Fragen starten';
$string['no_difficulty_level'] = 'keine Schwierigkeit';
$string['no_tags'] = 'keine Tags';
$string['no_votes'] = 'keine Bewertungen';
$string['no_practice'] = 'keine Durchführungen';
$string['no_comment'] = 'keine Kommentare';
$string['approved'] = '✓';
$string['not_approved'] = '✗';
$string['approve'] = 'Un-/Bestätigen';
$string['approveselectedscheck'] = 'Sind Sie sicher, dass Sie die folgenden Fragen un-/bestätigen wollen?<br /><br />{$a}';
$string['questionsinuse'] = '(* Die Fragen mit einem Stern werden bereits in Quizzes verwendet.)';
$string['creator_anonym_firstname'] = 'anonym';
$string['creator_anonym_lastname'] = 'anonym';

// Filters.
$string['filter_label_search'] = 'Suche';
$string['filter_label_question'] = 'Fragetitel';
$string['filter_label_approved'] = 'Bestätigte Fragen';
$string['filter_label_firstname'] = 'Vorname';
$string['filter_label_surname'] = 'Nachname';
$string['filter_label_createdate'] = 'Erstellt';
$string['filter_label_questiontext'] = 'Frageinhalt';
$string['filter_label_tags'] = 'Tag';
$string['filter_label_votes'] = 'Bewertung';
$string['filter_label_practice'] = 'Durchführungen';
$string['filter_label_comment'] = 'Kommentare';
$string['filter_label_difficulty_level'] = 'Schwierigkeiten';
$string['filter_ishigher'] = 'Ist höher';
$string['filter_islower'] = 'Ist tiefer';
$string['filter_label_show_mine'] = 'Meine Fragen';
$string['filter'] = 'Filter';

// Admin settings.
$string['rankingsettingsheader'] = 'Bewertungseinstellungen';
$string['settings_add_q_quantifier'] = 'Punkte für jede erstellte Frage';
$string['config_add_q_quantifier'] = 'Erhaltene Punkte für die Erstellung einer neuen Frage.';
$string['settings_vote_quantifier'] = 'Multiplikator für den Durchschnitt der empfangenen Sterne pro Frage';
$string['config_vote_quantifier'] = 'Zum Bsp. wenn der Multiplikator 3 ist und eine Frage mit einem Durchschnitt von 4,3 Sternen bewertet wird, erhält der Verfasser der Frage 13 Punkte (=RUNDEN(3*4.3;1)).';
$string['settings_correct_answered_q_quantifier'] = 'Punkte für jede richtige Antwort';
$string['config_correct_answered_q_quantifier'] = 'Erhaltene Punkte für die korrekte Beantwortung einer Frage.';
$string['settings_incorrect_answered_q_quantifier'] = 'Punkte für jede falsche Antwort';
$string['config_incorrect_answered_q_quantifier'] = 'Erhaltene Punkte für die falsche Beantwortung einer Frage.';

// Report Dashboard.
$string['reportquiz_dashboard_title'] = 'Statistiken';

// Report quiz.
$string['reportquiz_total_title'] = 'Statistik über Versuche';
$string['reportquiz_total_attempt'] = 'Anzahl der Quizdurchführungen';
$string['reportquiz_total_questions_answered'] = 'Total beantwortete Fragen';
$string['reportquiz_total_questions_right'] = 'Total richtig Beantwortet';
$string['reportquiz_total_questions_wrong'] = 'Falsch Beantwortet';
$string['reportquiz_total_obtained_marks'] = 'Erhaltene Punkte';
$string['reportquiz_summary_title'] = 'Ihre Quiz Durchführungen';
$string['reportquiz_total_users'] = 'Teilnehmerzahl';
$string['reportquiz_admin_title'] = 'Benutzer Statistik';

// Report quiz admin section.
$string['reportquiz_admin_total_title'] = 'Gesamttotal';
$string['reportquiz_admin_quizzes_title'] = 'Erstellte Quizzes';
$string['reportquiz_admin_quizzes_table_column_quizname'] = 'Quizname';
$string['reportquiz_admin_quizzes_table_column_qbehaviour'] = 'Quizverhalten';
$string['reportquiz_admin_quizzes_table_column_timecreated'] = 'Erstellt';
$string['reportquiz_admin_quizzes_table_link_to_quiz'] = 'Link zum Quiz';

// Report quiz stats.
$string['reportquiz_stats_title'] = 'Statistiken';
$string['reportquiz_stats_nr_of_questions'] = 'Anzahl Fragen im Quiz';
$string['reportquiz_stats_right_answered_questions'] = 'Sie haben richtig beantwortet';

$string['reportquiz_stats_nr_of_own_questions'] = 'Sie haben beigetragen';
$string['reportquiz_stats_nr_of_approved_questions'] = 'Anzahl der bestätigten Fragen';
$string['reportquiz_stats_avg_rating'] = 'Sie haben im durchschn. Bewertung';

$string['reportquiz_stats_own_grade_of_max'] = 'Ihre maximale Punktenzahl';

$string['reportquiz_stats_attempt'] = 'Anzahl Ihrer Durchführungen';
$string['reportquiz_stats_questions_answered'] = 'Gesamte Anzahl Ihrer Antworten';
$string['reportquiz_stats_questions_right'] = 'Gesamte Anzahl korrekter Antworten';

// Report rank.
$string['reportrank_title'] = 'Benutzerrangliste';
$string['reportrank_table_title'] = '- Rangliste';
$string['reportrank_table_column_rank'] = 'Rang';
$string['reportrank_table_column_fullname'] = 'Vollständiger Name';
$string['reportrank_table_column_points'] = 'Punkte';

// View.
$string['viewlib_please_select_question'] = 'Bitte wählen Sie eine Frage.';
$string['viewlib_please_contact_the_admin'] = 'Bitte kontaktieren Sie den Administrator.';

// Permission.
$string['studentquiz:submit'] = 'StudentQuiz absenden';
$string['studentquiz:view'] = 'StudentQuiz ansehen';
$string['studentquiz:addinstance'] = 'Neue Instanz hinzufügen';
$string['studentquiz:change'] = 'Mitteilung zur Fragenbearbeotung';
$string['studentquiz:approved'] = 'Mitteilung zur Fragenbestätitgung';
$string['studentquiz:unapproved'] = 'Mitteilung zum rückgängig machen der Fragenbestätitgung';
$string['studentquiz:emailnotifychange'] = 'Mitteilung zur Fragenbearbeotung';
$string['studentquiz:emailnotifyapproved'] = 'Mitteilung zur Fragenbestätitgung';
$string['studentquiz:emailnotifyunapproved'] = 'Mitteilung zum rückgängig machen der Fragenbestätitgung';

// Message provider.
$string['messageprovider:change'] = 'Mitteilung zur Fragenbearbeitung';
$string['messageprovider:approved'] = 'Mitteilung zur Fragenbestätitgung';
$string['messageprovider:unapproved'] = 'Mitteilung zum rückgängig machen der Fragenbestätitgung';

// Change notification email.
$string['emailchangebody'] = 'Hallo {$a->studentname},

Diese E-Mail informiert Sie, dass Ihre Frage \'{$a->questionname}\'
im Kurs \'{$a->coursename}\' von einer Lehrperson bearbeitet wurde.

Sie können die Frage über den folgenden Link betrachten: {$a->questionurl}.';
$string['emailchangesmall'] = 'Ihre Frage \'{$a->questionname}\' wurde von einer Lehrperson bearbeitet.';
$string['emailchangesubject'] = 'Frage wurde bearbeitet: {$a->questionname}';

// Approve notification email.
$string['emailapprovedbody'] = 'Hallo {$a->studentname},

Diese E-Mail informiert Sie, dass Ihre Frage \'{$a->questionname}\'
im Kurs \'{$a->coursename}\' von einer Lehrperson bestätigt wurde.

Sie können die Frage über den folgenden Link betrachten: {$a->questionurl}.';
$string['emailapprovedsmall'] = 'Ihre Frage \'{$a->questionname}\' wurde von einer Lehrperson bestätigt.';
$string['emailapprovedsubject'] = 'Frage wurde bestätigt: {$a->questionname}';

// Unapprove notification email.
$string['emailunapprovedbody'] = 'Hallo {$a->studentname},

Diese E-Mail informiert Sie, dass die Bestätigung Ihre Frage \'{$a->questionname}\'
im Kurs \'{$a->coursename}\' von einer Lehrperson rückgängig gemacht wurde.

Sie können die Frage über den folgenden Link betrachten: {$a->questionurl}.';
$string['emailunapprovedsmall'] = 'Die Bestätigung Ihrer Frage \'{$a->questionname}\' wurde von einer Lehrperson rückgängig gemacht.';
$string['emailunapprovedsubject'] = 'Bestätigung der Frage wurde rückgängig gemacht: {$a->questionname}';
