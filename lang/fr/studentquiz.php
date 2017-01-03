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
 * French strings for StudentQuiz
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
$string['modulename_help'] = 'L\'activité StudentQuiz permet aux élèves d\'ajouter des questions à la foule. Dans l\'aperçu du StudentQuiz, les élèves peuvent filtrer les questions. Ils peuvent également utiliser les questions filtrées dans la foule pour pratiquer. L\'enseignant a une option pour anonymiser les créateurs des questions. <br> <br> L\'activité StudentQuiz récompense les élèves avec des points pour les motiver à ajouter et à pratiquer. Les Points sont listés dans un classement. <br> <br> Pour plus d\'informations, lisez le <a href = "https://github.com/frankkoch/moodle-mod_studentquiz/blob/master/manuals/User-Manual.pdf "> Manuel d\'utilisateur </a>.';
$string['studentquizfieldset'] = 'Example pepersonnalisé du fieldset';
$string['studentquizname'] = 'Nom StudentQuiz';
$string['studentquizname_help'] = 'Nom StudentQuiz';
$string['anonymous_checkbox_label'] = 'Rendre etudiant anonyme';
$string['quiz_advanced_settings_header'] = 'Réglages avancés';
$string['quizpracticebehaviour'] = 'Évaluer et commenter';
$string['quizpracticebehaviourhelp'] = 'Évaluer et commenter des questions';
$string['quizpracticebehaviourhelp_help'] = 'Évaluer et commenter question';

$string['studentquiz'] = 'studentquiz';
$string['pluginadministration'] = 'Administration StudentQuiz';
$string['pluginname'] = 'StudentQuiz';
$string['vote_column_name'] = 'Évaluation';
$string['practice_column_name'] = 'Tenues';
$string['comment_column_name'] = 'Commentaires';
$string['difficulty_level_column_name'] = 'Difficulté';
$string['approved_column_name'] = 'Attesté';
$string['vote_points'] = 'Points';
$string['tag_column_name'] = 'Étiquettes';
$string['start_quiz_button'] = 'Démarrer le quiz';
$string['nav_question_and_quiz'] = 'Quiz et Questions';
$string['nav_report'] = 'Rapport';
$string['nav_report_quiz'] = 'Quiz';
$string['nav_report_rank'] = 'Rang';
$string['nav_export'] = 'Export';
$string['nav_import'] = 'Import';
$string['nav_questionbank'] = 'Banque de questions';
$string['anonymrankhelp'] = 'Anonymiser';
$string['anonymrankhelp_help'] = 'Rendre anonyme les etudiants dans l\'aperçu et dans le tableau des rangs.';
$string['createnewquestionfirst'] = 'Créer une première question';
$string['createnewquestion'] = 'Créer une nouvelle question';
$string['createnewquizfromfilter'] = 'Démarrer les questions filtrées';
$string['no_difficulty_level'] = 'pas de difficulté';
$string['no_tags'] = 'pas de tags';
$string['no_votes'] = 'pas de notes';
$string['no_practice'] = 'pas de tentative';
$string['no_comment'] = 'pas de commentaires';
$string['approved'] = '✓';
$string['not_approved'] = '✗';
$string['approve'] = 'Des-/Attester';
$string['approveselectedscheck'] = 'Voulez-vous vraiment desattester les questions suivantes?<br /><br />{$a}';
$string['questionsinuse'] = '(* Les questions marquées d\'un astérisque sont déjà utilisées dans certains quiz.)';
$string['creator_anonym_firstname'] = 'anonyme';
$string['creator_anonym_lastname'] = 'anonyme';

// Filters.
$string['filter_label_search'] = 'Chercher';
$string['filter_label_question'] = 'Titre de question';
$string['filter_label_approved'] = 'Questions attesté';
$string['filter_label_firstname'] = 'Prénom';
$string['filter_label_surname'] = 'Nom de famille';
$string['filter_label_createdate'] = 'Création';
$string['filter_label_questiontext'] = 'Contenu de la question';
$string['filter_label_tags'] = 'Étiquette';
$string['filter_label_votes'] = 'Évaluation';
$string['filter_label_practice'] = 'Tentatives';
$string['filter_label_comment'] = 'Commentaires';
$string['filter_label_difficulty_level'] = 'Difficulté';
$string['filter_ishigher'] = 'est plus élevé';
$string['filter_islower'] = 'Est plus bas';
$string['filter_label_show_mine'] = 'Mes questions';
$string['filter'] = 'Filtre';

// Admin settings.
$string['rankingsettingsheader'] = 'Réglage des Rangs';
$string['settings_add_q_quantifier'] = 'Points pour chaque question créée';
$string['config_add_q_quantifier'] = 'Points reçus pour la création d\'une nouvelle question.';
$string['settings_vote_quantifier'] = 'Multiplicateur pour la moyenne des étoiles reçus pour une question';
$string['config_vote_quantifier'] = 'Par exemple. Si le multiplicateur est 3 et une question est évaluée avec une moyenne de 4,3 étoiles, l\'auteur de la question recevra 13 points (= ROUND (3 * 4.3; 1)).';
$string['settings_correct_answered_q_quantifier'] = 'Points pour chaque réponse correcte';
$string['config_correct_answered_q_quantifier'] = 'Points reçus pour avoir répondu correctement à une question.';
$string['settings_incorrect_answered_q_quantifier'] = 'Points pour chaque mauvaise réponse';
$string['config_incorrect_answered_q_quantifier'] = 'Points reçus pour avoir mal repondu à une question.';

// Report Dashboard.
$string['reportquiz_dashboard_title'] = 'Statistiques';

// Report quiz.
$string['reportquiz_total_title'] = 'Statistique des tentatives';
$string['reportquiz_total_attempt'] = 'Nombre de tentatives';
$string['reportquiz_total_questions_answered'] = 'Questions Répondu';
$string['reportquiz_total_questions_right'] = 'Réponses correctes';
$string['reportquiz_total_questions_wrong'] = 'Réponses fausses';
$string['reportquiz_total_obtained_marks'] = 'Note reçu';
$string['reportquiz_summary_title'] = 'Votre tenues du quiz';
$string['reportquiz_total_users'] = 'Nombre de participants';
$string['reportquiz_admin_title'] = 'Statistique d\'utilisateur';

// Report quiz admin section.
$string['reportquiz_admin_total_title'] = 'Total général';
$string['reportquiz_admin_quizzes_title'] = 'Quiz créé';
$string['reportquiz_admin_quizzes_table_column_quizname'] = 'Nom du Quiz';
$string['reportquiz_admin_quizzes_table_column_qbehaviour'] = 'Conduit Quiz';
$string['reportquiz_admin_quizzes_table_column_timecreated'] = 'Créé';
$string['reportquiz_admin_quizzes_table_link_to_quiz'] = 'Lien vers le quiz';

// Report quiz stats.
$string['reportquiz_stats_title'] = 'Statistiques';
$string['reportquiz_stats_nr_of_questions'] = 'Nombre de questions dans ce quiz';
$string['reportquiz_stats_right_answered_questions'] = 'Vous avez répondu correctement';
$string['reportquiz_stats_nr_of_own_questions'] = 'Vous avez contribué';


$string['reportquiz_stats_nr_of_approved_questions'] = 'Nombre de question attesté';
$string['reportquiz_stats_avg_rating'] = 'Vous avez une moyenne d\'évaluation';


$string['reportquiz_stats_own_grade_of_max'] = 'Votre niveau maximal';

$string['reportquiz_stats_attempt'] = 'Nombre que vous avez exécuté le quiz';
$string['reportquiz_stats_questions_answered'] = 'Total de vos réponses';
$string['reportquiz_stats_questions_right'] = 'Total des bonnes réponses';

// Report rank.
$string['reportrank_title'] = 'Classement des etudiants';
$string['reportrank_table_title'] = '- Classement';
$string['reportrank_table_column_rank'] = 'Rang';
$string['reportrank_table_column_fullname'] = 'Nom';
$string['reportrank_table_column_points'] = 'Points';

// View.
$string['viewlib_please_select_question'] = 'Veuillez sélectionner une question.';
$string['viewlib_please_contact_the_admin'] = 'Veuillez contacter l\'administrateur.';

// Permission.
$string['studentquiz:submit'] = 'Envoyer sur StudentQuiz';
$string['studentquiz:view'] = 'Voir StudentQuiz';
$string['studentquiz:addinstance'] = 'Ajouter une nouvelle instance';
$string['studentquiz:change'] = 'Notification de changement de question';
$string['studentquiz:approved'] = 'Notification de attesté de question';
$string['studentquiz:unapproved'] = 'Notification de deattesté de question';
$string['studentquiz:emailnotifychange'] = 'Notification de changement de question';
$string['studentquiz:emailnotifyapproved'] = 'Notification de attesté de question';
$string['studentquiz:emailnotifyunapproved'] = 'Notification de deattesté de question';

// Message provider.
$string['messageprovider:change'] = 'Notification de changement de question';
$string['messageprovider:approved'] = 'Notification de attesté de question';
$string['messageprovider:unapproved'] = 'Notification de deattesté de question';

// Change notification email.
$string['emailchangebody'] = 'Cher {$a->studentname},

Cet e-mail vous informe que votre question \'{$a->questionname}\'
dans le cours \'{$a->coursename}\' a été modifié par un enseignant.

Vous pouvez examiner cette question à: {$a->questionurl}.';
$string['emailchangesmall'] = 'Votre question \'{$a->questionname}\' a été modifié par un enseignant.';
$string['emailchangesubject'] = 'Question modifié: {$a->questionname}';

// Approve notification email.
$string['emailapprovedbody'] = 'Dear {$a->studentname},

Cet e-mail vous informe que votre question \'{$a->questionname}\'
dans le cours \'{$a->coursename}\' a été attesté par un enseignant.

Vous pouvez examiner cette question à: {$a->questionurl}.';
$string['emailapprovedsmall'] = 'Votre question \'{$a->questionname}\' a été attesté par un enseignant.';
$string['emailapprovedsubject'] = 'Question attesté: {$a->questionname}';

// Unapprove notification email.
$string['emailunapprovedbody'] = 'Dear {$a->studentname},

Cet e-mail vous informe que votre question \'{$a->questionname}\'
dans le cours \'{$a->coursename}\' a été deattesté par un enseignant.

Vous pouvez examiner cette question à: {$a->questionurl}.';
$string['emailunapprovedsmall'] = 'Votre question \'{$a->questionname}\' a été deattesté par un enseignant.';
$string['emailunapprovedsubject'] = 'Question deattesté: {$a->questionname}';
