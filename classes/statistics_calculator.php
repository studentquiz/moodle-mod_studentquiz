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

namespace mod_studentquiz;

use \core_question\local\bank\question_version_status;

/**
 * Staticstic calculator class contain all the logic related to student quiz stats.
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class statistics_calculator {

    /**
     * Query helper for attempt stats
     *
     * @return string
     * TODO: Refactor: There must be a better way to do this!
     */
    private static function get_attempt_stat_select() {
        return "SELECT statspercategory.userid AS userid,
                   -- Aggregate values over all categories in cm context.
                   -- Note: Max() of equals is faster than Sum() of groups.
                   -- See: https://dev.mysql.com/doc/refman/5.7/en/group-by-optimization.html.
                   MAX(points) AS points, MAX(questions_created) AS questions_created,
                   MAX(questions_created_and_rated) AS questions_created_and_rated, MAX(questions_approved) AS questions_approved,
                   MAX(questions_disapproved) AS questions_disapproved,
                   MAX(rates_received) AS rates_received, MAX(rates_average) AS rates_average,
                   MAX(question_attempts) AS question_attempts, MAX(question_attempts_correct) AS question_attempts_correct,
                   MAX(question_attempts_incorrect) AS question_attempts_incorrect,
                   MAX(last_attempt_exists) AS last_attempt_exists, MAX(last_attempt_correct) AS last_attempt_correct,
                   MAX(last_attempt_incorrect) AS last_attempt_incorrect
              -- Select for each question category in context.
              FROM (
                     SELECT u.id AS userid, qc.id AS category,
                            -- Calculate points.
                            COALESCE (
                                       ROUND (
                                               -- Questions created.
                                               COALESCE(creators.countq, 0) * :questionquantifier +
                                               -- Questions approved.
                                               COALESCE(approvals.approved, 0) * :approvedquantifier +
                                               -- Rating.
                                               COALESCE(rates.avgv, 0) * (COALESCE(creators.countq, 0) -
                                                   COALESCE(rates.not_rated_questions, 0)) * :ratequantifier +
                                               -- Correct answers.
                                               COALESCE(lastattempt.last_attempt_correct, 0) * :correctanswerquantifier +
                                               -- Incorrect answers.
                                               COALESCE(lastattempt.last_attempt_incorrect, 0) * :incorrectanswerquantifier,
                                               1
                                             ),
                                       0
                                     ) AS points,
                            -- Questions created.
                            COALESCE(creators.countq, 0) AS questions_created,
                            -- Questions created and rated.
                            COALESCE(COALESCE(creators.countq, 0) - COALESCE(rates.not_rated_questions, 0),
                                0) AS questions_created_and_rated,
                            -- Questions approved.
                            COALESCE(approvals.approved, 0) AS questions_approved,
                            -- Questions disapproved.
                            COALESCE(approvals.disapproved, 0) AS questions_disapproved,
                            -- Questions rating received.
                            COALESCE(rates.countv, 0) AS rates_received,
                            COALESCE(rates.avgv, 0) AS rates_average,
                            -- Question attempts.
                            COALESCE(attempts.counta, 0) AS question_attempts,
                            COALESCE(attempts.countright, 0) AS question_attempts_correct,
                            COALESCE(attempts.countwrong, 0) AS question_attempts_incorrect,
                            -- Last attempt.
                            COALESCE(lastattempt.last_attempt_exists, 0) AS last_attempt_exists,
                            COALESCE(lastattempt.last_attempt_correct, 0) AS last_attempt_correct,
                            COALESCE(lastattempt.last_attempt_incorrect, 0) AS last_attempt_incorrect
               -- WARNING: the trailing ) is intentionally missing, found in mod_studentquiz_user_stats var statsbycat
               -- Following newline is intentional because this string is concatenated
           ";
    }

    /**
     * Helper query for attempt stat joins
     *
     * @param int $cmid Course module id.
     * @param int $groupid Group id.
     * @param array $excluderoles Roles list to exclude.
     * @return \core\dml\sql_join Join object.
     * TODO: Refactor: There must be a better way to do this!
     */
    private static function get_attempt_stat_joins($cmid, $groupid, $excluderoles = []): \core\dml\sql_join {
        $join = " FROM {studentquiz} sq
             -- Get this Studentquiz Question category.
             JOIN {context} con ON con.instanceid = sq.coursemodule
                  AND con.contextlevel = ".CONTEXT_MODULE."
             JOIN {question_categories} qc ON qc.contextid = con.id
             -- Only enrolled users.
             JOIN {course} c ON c.id = sq.course
             JOIN {context} cctx ON cctx.instanceid = c.id
                  AND cctx.contextlevel = ".CONTEXT_COURSE."
             JOIN {role_assignments} ra ON cctx.id = ra.contextid
             JOIN {user} u ON u.id = ra.userid";
        if (!empty($excluderoles)) {
            $join .= "
            -- Only not excluded roles
            JOIN {role} r ON r.id = ra.roleid
                AND r.id NOT IN (".implode(',', $excluderoles).")";
        }

        // We just count the questions create by user in current group.
        $groupjoinquestioncreatebyuser = utils::groups_get_questions_joins($groupid);
        $join .= "
        -- Question created by user.
        LEFT JOIN (
                    SELECT count(*) AS countq, q.createdby AS creator
                      FROM {studentquiz} sq
                      -- Get this StudentQuiz question.
                      JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
                      JOIN {question_references} qr ON qr.itemid = sqq.id
                           AND qr.component = 'mod_studentquiz'
                           AND qr.questionarea = 'studentquiz_question'
                      JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                      JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready1
                                  )
                      JOIN {question} q ON qv.questionid = q.id
                     WHERE sqq.hidden = 0
                           AND q.parent = 0
                           AND sq.coursemodule = :cmid4";
        if ($groupjoinquestioncreatebyuser->wheres) {
            $join .= "
                            AND $groupjoinquestioncreatebyuser->wheres";
        }
        $join .= "
                  GROUP BY q.createdby
                  ) creators ON creators.creator = u.id";

        // We just count the approved questions in current group.
        $groupjoinapprovedquestion = utils::groups_get_questions_joins($groupid);
        $join .= "
        -- Approved questions.
        LEFT JOIN (
                    SELECT count(*) AS countq, q.createdby AS creator,
                    COUNT(CASE WHEN sqq.state = 0 THEN q.id END) as disapproved,
	                COUNT(CASE WHEN sqq.state = 1 THEN q.id END) as approved
                      FROM {studentquiz} sq
                      JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
                      JOIN {question_references} qr ON qr.itemid = sqq.id
                           AND qr.component = 'mod_studentquiz'
                           AND qr.questionarea = 'studentquiz_question'
                      JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                      JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready2
                                  )
                      JOIN {question} q ON qv.questionid = q.id
                     WHERE q.parent = 0
                           AND sqq.hidden = 0
                           AND sq.coursemodule = :cmid5";
        if ($groupjoinapprovedquestion->wheres) {
            $join .= "
                            AND $groupjoinapprovedquestion->wheres";
        }
        $join .= "
                   GROUP BY q.createdby
                   ) approvals ON approvals.creator = u.id";

        // We just count the ratings of current group's members.
        $groupjoinratingsql = utils::groups_get_questions_joins($groupid);
        $join .= "
        -- Average of Average Rating of own questions.
        LEFT JOIN (
                    SELECT createdby, AVG(avg_rate_perq) AS avgv, SUM(num_rate_perq) AS countv,
                           SUM(question_not_rated) AS not_rated_questions
                      FROM (
                             SELECT q.id, q.createdby AS createdby, AVG(sqv.rate) AS avg_rate_perq,
                                    COUNT(sqv.rate) AS num_rate_perq,
                                    MAX(CASE WHEN sqv.id IS NULL THEN 1 ELSE 0 END) AS question_not_rated
                               FROM {studentquiz} sq
                               JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
                               JOIN {question_references} qr ON qr.itemid = sqq.id
                                    AND qr.component = 'mod_studentquiz'
                                    AND qr.questionarea = 'studentquiz_question'
                               JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                               JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready3
                                  )
                               JOIN {question} q ON qv.questionid = q.id
                          LEFT JOIN {studentquiz_rate} sqv ON sqq.id = sqv.studentquizquestionid
                              WHERE q.parent = 0
                                    AND sqq.hidden = 0
                                    AND sq.coursemodule = :cmid6";
        if ($groupjoinratingsql->wheres) {
            $join .= "
                                    AND $groupjoinratingsql->wheres";
        }

        $join .= "
                           GROUP BY q.id, q.createdby
                           ) avgratingperquestion
                  GROUP BY createdby
                  ) rates ON rates.createdby = u.id";

        // We just collect the attempts for questions in current group.
        $groupjoinattemptsql = utils::groups_get_questions_joins($groupid);
        $join .= "
        LEFT JOIN (
                    SELECT sp.userid, COUNT(*) AS last_attempt_exists, SUM(lastanswercorrect) AS last_attempt_correct,
                           SUM(CASE WHEN attempts > 0 and lastanswercorrect = 0 THEN 1 ELSE 0 END) AS last_attempt_incorrect
                      FROM {studentquiz_progress} sp
                      JOIN {studentquiz_question} sqq ON sp.studentquizquestionid = sqq.id
                      JOIN {studentquiz} sq ON sq.id = sqq.studentquizid
                      JOIN {question_references} qr ON qr.itemid = sqq.id
                           AND qr.component = 'mod_studentquiz'
                           AND qr.questionarea = 'studentquiz_question'
                      JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                      JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready4
                                  )
                      JOIN {question} q ON qv.questionid = q.id
                     WHERE sq.coursemodule = :cmid2
                           AND sqq.hidden = 0";
        if ($groupjoinattemptsql->wheres) {
            $join .= "
                           AND $groupjoinattemptsql->wheres";
        }

        // We just calculate the stats for questions in current group.
        $groupjoinstatstsql = utils::groups_get_questions_joins($groupid);
        $join .= "
                  GROUP BY sp.userid
                  ) lastattempt ON lastattempt.userid = u.id
        LEFT JOIN (
                    SELECT SUM(attempts) AS counta, SUM(correctattempts) AS countright,
                           SUM(attempts - correctattempts) AS countwrong, sp.userid AS userid
                      FROM {studentquiz_progress} sp
                      JOIN {studentquiz_question} sqq ON sp.studentquizquestionid = sqq.id
                      JOIN {studentquiz} sq ON sq.id = sqq.studentquizid
                      JOIN {question_references} qr ON qr.itemid = sqq.id
                           AND qr.component = 'mod_studentquiz'
                           AND qr.questionarea = 'studentquiz_question'
                      JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                      JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready5
                                  )
                      JOIN {question} q ON qv.questionid = q.id
                     WHERE sq.coursemodule = :cmid1
                           AND sqq.hidden = 0";

        if ($groupjoinstatstsql->wheres) {
            $join .= "
                           AND $groupjoinstatstsql->wheres";
        }

        $join .= "
                  GROUP BY sp.userid
                  ) attempts ON attempts.userid = u.id";

        // Question attempts: sum of number of graded attempts per question.
        $groupjoingsql = utils::sq_groups_get_members_join($groupid, 'u.id', \context_module::instance($cmid));
        $join .= ' ' . $groupjoingsql->joins;
        $where = "
            WHERE sq.coursemodule = :cmid3";

        if ($groupjoingsql->wheres) {
            $where .= "
                  AND $groupjoingsql->wheres";
        }

        $params = $groupjoinquestioncreatebyuser->params + $groupjoinapprovedquestion->params +
            $groupjoinratingsql->params + $groupjoinattemptsql->params + $groupjoinstatstsql->params + $groupjoingsql->params;
        return new \core\dml\sql_join($join, $where, $params);
    }

    /**
     * mod_studentquiz_helper_attempt_stat_joins params
     *
     * @param int $cmid course module id
     * @param null|\stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
     * @param null|int $userid
     * @return array
     */
    private static function get_attempt_stat_joins_params($cmid, $quantifiers = null, $userid = null): array {
        $params = [
            'cmid1' => $cmid,
            'cmid2' => $cmid,
            'cmid3' => $cmid,
            'cmid4' => $cmid,
            'cmid5' => $cmid,
            'cmid6' => $cmid,
            'cmid7' => $cmid,
            'ready1' => question_version_status::QUESTION_STATUS_READY,
            'ready2' => question_version_status::QUESTION_STATUS_READY,
            'ready3' => question_version_status::QUESTION_STATUS_READY,
            'ready4' => question_version_status::QUESTION_STATUS_READY,
            'ready5' => question_version_status::QUESTION_STATUS_READY,
        ];
        if ($quantifiers) {
            $params['questionquantifier'] = $quantifiers->question;
            $params['approvedquantifier'] = $quantifiers->approved;
            $params['ratequantifier'] = $quantifiers->rate;
            $params['correctanswerquantifier'] = $quantifiers->correctanswer;
            $params['incorrectanswerquantifier'] = $quantifiers->incorrectanswer;
        }
        if ($userid) {
            $params['userid'] = $userid;
        }
        return $params;
    }

    /**
     * Get aggregated studentquiz data
     * @param int $cmid Course module id of the StudentQuiz considered.
     * @param int $groupid Group id.
     * @return \moodle_recordset of paginated ranking table
     */
    public static function get_community_stats($cmid, $groupid) {
        global $DB;
        $select = 'SELECT '
            .' count(*) participants,'
            // Calculate points.
            // TODO: Calc Points if needed - it's messy.
            // questions created.
            .' COALESCE(sum(creators.countq), 0) questions_created,'
            // Questions approved.
            .' COALESCE(sum(approvals.countq), 0) questions_approved,'
            // Questions rating received.
            .' COALESCE(sum(rates.countv), 0) rates_received,'
            .' COALESCE(avg(rates.avgv), 0) rates_average,'
            // Question attempts.
            .' COALESCE(count(1), 0) participated,'
            .' COALESCE(sum(attempts.counta), 0) question_attempts,'
            .' COALESCE(sum(attempts.countright), 0) question_attempts_correct,'
            .' COALESCE(sum(attempts.countwrong), 0) question_attempts_incorrect,'
            // Last attempt.
            .' COALESCE(sum(lastattempt.last_attempt_exists), 0) last_attempt_exists,'
            .' COALESCE(sum(lastattempt.last_attempt_correct), 0) last_attempt_correct,'
            .' COALESCE(sum(lastattempt.last_attempt_incorrect), 0) last_attempt_incorrect';
        $attemptstastjoins = self::get_attempt_stat_joins($cmid, $groupid);
        $params = self::get_attempt_stat_joins_params($cmid);
        $params += $attemptstastjoins->params;
        $rs = $DB->get_record_sql("$select {$attemptstastjoins->joins} {$attemptstastjoins->wheres}", $params);
        return $rs;
    }

    /**
     * Get aggregated studentquiz data
     *
     * @param int $cmid Course module id of the StudentQuiz considered.
     * @param int $groupid Group id.
     * @param \stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
     * @param int $userid User id.
     * @return array array of user ranking stats
     * TODO: use mod_studentquiz_report_record type
     */
    public static function get_user_stats($cmid, $groupid, $quantifiers, $userid) {
        global $DB;
        $select = self::get_attempt_stat_select();
        $attemptstastjoins = self::get_attempt_stat_joins($cmid, $groupid);
        $addwhere = ' AND u.id = :userid ';
        $statsbycat = ' ) statspercategory GROUP BY userid';
        $params = self::get_attempt_stat_joins_params($cmid, $quantifiers, $userid);
        $params += $attemptstastjoins->params;
        $rs = $DB->get_record_sql("$select {$attemptstastjoins->joins} {$attemptstastjoins->wheres} $addwhere $statsbycat ",
            $params);
        return $rs;
    }

    /**
     * Get Paginated ranking data ordered (DESC) by points, questions_created, questions_approved, rates_average
     * @param int $cmid Course module id of the StudentQuiz considered.
     * @param int $groupid Group id
     * @param \stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
     * @param []int $excluderoles array of role ids to exclude
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return \moodle_recordset of paginated ranking table
     */
    public static function get_user_ranking_table($cmid, $groupid, $quantifiers, $excluderoles = [],
        $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $select = self::get_attempt_stat_select();
        $attemptstastjoins = self::get_attempt_stat_joins($cmid, $groupid, $excluderoles);
        $statsbycat = ' ) statspercategory GROUP BY userid';
        $order = ' ORDER BY points DESC, questions_created DESC, questions_approved DESC, rates_average DESC, '
            .' question_attempts_correct DESC, question_attempts_incorrect ASC ';
        $params = self::get_attempt_stat_joins_params($cmid, $quantifiers);
        $params += $attemptstastjoins->params;
        $res = $DB->get_recordset_sql("$select {$attemptstastjoins->joins} {$attemptstastjoins->wheres} $statsbycat $order",
            $params, $limitfrom, $limitnum);
        return $res;
    }

    /**
     * This query collects aggregated information about the questions in this StudentQuiz.
     *
     * @param int $cmid Course module id.
     * @param int $groupid Group id.
     * @return array array of question stats.
     */
    public static function get_question_stats($cmid, $groupid) {
        global $DB;

        $sql = "SELECT COUNT(*) AS questions_available,
                   AVG(rating.avg_rating) AS average_rating,
                   SUM(CASE WHEN sqq.state = 1 THEN 1 ELSE 0 END) AS questions_approved
              FROM {studentquiz} sq
              -- Get this StudentQuiz question.
              JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
              JOIN {question_references} qr ON qr.itemid = sqq.id
                   AND qr.component = 'mod_studentquiz'
                   AND qr.questionarea = 'studentquiz_question'
              JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
              JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready1
                                  )
              -- Only enrolled users.
              JOIN {question} q ON q.id = qv.questionid
         LEFT JOIN (
                     SELECT q.id AS questionid, COALESCE(AVG(sqr.rate),0) AS avg_rating
                       FROM {studentquiz} sq
                       JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
                       JOIN {question_references} qr ON qr.itemid = sqq.id
                       JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                            AND qr.component = 'mod_studentquiz'
                            AND qr.questionarea = 'studentquiz_question'
                       JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready2
                                  )
                       JOIN {question} q ON q.id = qv.questionid
                  LEFT JOIN {studentquiz_rate} sqr ON sqr.studentquizquestionid = sqq.id
                      WHERE sq.coursemodule = :cmid2
                   GROUP BY q.id
                   ) rating ON rating.questionid = q.id ";
        $sqlwheres = [
            'sqq.hidden = 0',
            'q.parent = 0',
            'sq.coursemodule = :cmid1'
        ];
        $params = [
            'cmid1' => $cmid,
            'cmid2' => $cmid,
            'ready1' => question_version_status::QUESTION_STATUS_READY,
            'ready2' => question_version_status::QUESTION_STATUS_READY,
        ];

        if ($groupid) {
            $groupjoinsql = utils::groups_get_questions_joins($groupid);
            $sql .= $groupjoinsql->joins;
            $sqlwheres[] = $groupjoinsql->wheres;
            $params += $groupjoinsql->params;
        }

        $sql .= ' WHERE ' . implode(' AND ', $sqlwheres);
        $rs = $DB->get_record_sql($sql, $params);

        return $rs;
    }
}
