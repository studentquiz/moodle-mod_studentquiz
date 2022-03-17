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

namespace mod_studentquiz\local;

/**
 * Helper class for StudentQuiz
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_helper {

    /**
     * @var int STATE_DISAPPROVED state constant for disapproved
     */
    const STATE_DISAPPROVED = 0;

    /**
     * @var int STATE_APPROVED state constant for approved
     */
    const STATE_APPROVED = 1;

    /**
     * @var int STATE_NEW state constant for new
     */
    const STATE_NEW = 2;

    /**
     * @var int STATE_CHANGED state constant for changed
     */
    const STATE_CHANGED = 3;

    /**
     * @var int STATE_HIDE state constant for hidden
     */
    const STATE_HIDE = 4;

    /**
     * @var int STATE_DELETE state constant for deleted
     */
    const STATE_DELETE = 5;

    /**
     * @var int STATE_SHOW state constant for show
     */
    const STATE_SHOW = 6;

    /**
     * @var int STATE_REVIEWABLE state constant for reviewable.
     */
    const STATE_REVIEWABLE = 7;

    /**
     * Statename offers string representation for state codes. Probably only use for translation hints.
     * @var array constant to text
     */
    public static $statename = array(
        self::STATE_DISAPPROVED => 'disapproved',
        self::STATE_APPROVED => 'approved',
        self::STATE_NEW => 'new',
        self::STATE_CHANGED => 'changed',
        self::STATE_REVIEWABLE => 'reviewable',
        self::STATE_HIDE => 'hidden',
        self::STATE_DELETE => 'deleted',
    );

    /** Get list description of state name.
     * That is the past participle in singular.
     *
     * @return array List descriptions of state name.
     */
    public static function get_state_descriptions(): array {
        return [
            self::STATE_DISAPPROVED => get_string('state_disapproved', 'studentquiz'),
            self::STATE_APPROVED => get_string('state_approved', 'studentquiz'),
            self::STATE_CHANGED => get_string('state_changed', 'studentquiz'),
            self::STATE_HIDE => get_string('state_hidden', 'studentquiz'),
            self::STATE_DELETE => get_string('state_deleted', 'studentquiz'),
            self::STATE_SHOW => get_string('state_shown', 'studentquiz'),
            self::STATE_NEW => get_string('state_new', 'studentquiz'),
            self::STATE_REVIEWABLE => get_string('state_reviewable', 'studentquiz'),
        ];
    }
}
