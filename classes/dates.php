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
 * Contains the class for fetching the important dates in mod_studentquiz for a given module instance and a user.
 *
 * @package   mod_studentquiz
 * @copyright 2026 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_studentquiz;

use core\activity_dates;

/**
 * Class for fetching the important dates in mod_studentquiz for a given module instance and a user.
 *
 * @package   mod_studentquiz
 * @copyright 2026 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dates extends activity_dates {
    /**
     * Returns a list of important dates in mod_studentquiz
     *
     * @return array
     */
    protected function get_dates(): array {

        [$course, $cm] = get_course_and_cm_from_cmid($this->cm->id);
        $context = \context_module::instance($cm->id);

        $studentquiz = mod_studentquiz_load_studentquiz($this->cm->id, $context->id);

        $timeopensubmission = $studentquiz->opensubmissionfrom ?? null;
        $timeclosesubmission = $studentquiz->closesubmissionfrom ?? null;
        $timeopenanswering = $studentquiz->openansweringfrom ?? null;
        $timecloseanswering = $studentquiz->closeansweringfrom ?? null;

        $now = time();
        $dates = [];

        if ($timeopensubmission) {
            $openlabelid = $timeopensubmission > $now ? 'activitydate:openssubmission' : 'activitydate:openedsubmission';
            $date = [
                'dataid' => 'timeopensubmission',
                'label' => get_string($openlabelid, 'studentquiz'),
                'timestamp' => (int) $timeopensubmission,
            ];
            $dates[] = $date;
        }

        if ($timeclosesubmission) {
            $closelabelid = $timeclosesubmission > $now ? 'activitydate:closessubmission' : 'activitydate:closedsubmission';
            $date = [
                'dataid' => 'timeclosesubmission',
                'label' => get_string($closelabelid, 'studentquiz'),
                'timestamp' => (int) $timeclosesubmission,
            ];
            $dates[] = $date;
        }

        if ($timeopenanswering) {
            $openlabelid = $timeopenanswering > $now ? 'activitydate:opensanswering' : 'activitydate:openedanswering';
            $date = [
                'dataid' => 'timeopenanswering',
                'label' => get_string($openlabelid, 'studentquiz'),
                'timestamp' => (int) $timeopenanswering,
            ];
            $dates[] = $date;
        }

        if ($timecloseanswering) {
            $closelabelid = $timecloseanswering > $now ? 'activitydate:closesanswering' : 'activitydate:closedanswering';
            $date = [
                'dataid' => 'timecloseanswering',
                'label' => get_string($closelabelid, 'studentquiz'),
                'timestamp' => (int) $timecloseanswering,
            ];
            $dates[] = $date;
        }
        return $dates;
    }
}
