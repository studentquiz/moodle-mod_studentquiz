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

namespace mod_studentquiz\courseformat;

use cm_info;
use core\activity_dates;
use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\url;
use core_calendar\output\humandate;
use core_courseformat\local\overview\overviewitem;
use mod_studentquiz\manager;

/**
 * StudentQuiz overview integration (for Moodle 5.1+)
 *
 * @package   mod_studentquiz
 * @copyright 2026 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * @var manager the studentquiz manager.
     */
    private manager $manager;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
    ) {
        parent::__construct($cm);
        $this->manager = manager::create_from_coursemodule($cm);
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        $url = new url(
            '/mod/studentquiz/view.php',
            ['id' => $this->cm->id],
        );

        $text = get_string('view');

        if (
            class_exists(button::class) &&
            (new \ReflectionClass(button::class))->hasConstant('BODY_OUTLINE')
        ) {
            $bodyoutline = button::BODY_OUTLINE;
            $buttonclass = $bodyoutline->classes();
        } else {
            $buttonclass = "btn btn-outline-secondary";
        }

        $content = new action_link($url, $text, null, ['class' => $buttonclass]);
        return new overviewitem(get_string('actions'), $text, $content, text_align::CENTER);
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'submission_opens' => $this->get_extra_opensubmission(),
            'submission_closes' => $this->get_extra_closesubmission(),
            'answering_opens' => $this->get_extra_openanswering(),
            'answering_closes' => $this->get_extra_closeanswering(),
        ];
    }

    /**
     * Get the submission opens date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_opensubmission(): ?overviewitem {
        global $USER;
        $cminfo = cm_info::create($this->cm);
        $dates = activity_dates::get_dates_for_module($cminfo, $USER->id);
        $opensubmissiondate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeopensubmission') {
                $opensubmissiondate = $date['timestamp'];
                break;
            }
        }
        if (empty($opensubmissiondate)) {
            return new overviewitem(
                name: get_string('submissionopen', 'studentquiz'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($opensubmissiondate);

        return new overviewitem(
            name: get_string('submissionopen', 'studentquiz'),
            value: $opensubmissiondate,
            content: $content,
        );
    }

    /**
     * Get the submission closes date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_closesubmission(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $closesubmissiondate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeclosesubmission') {
                $closesubmissiondate = $date['timestamp'];
                break;
            }
        }
        if (empty($closesubmissiondate)) {
            return new overviewitem(
                name: get_string('submissionclose', 'studentquiz'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($closesubmissiondate);

        return new overviewitem(
            name: get_string('submissionclose', 'studentquiz'),
            value: $closesubmissiondate,
            content: $content,
        );
    }

    /**
     * Get the answering opens date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_openanswering(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $openansweringdate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeopenanswering') {
                $openansweringdate = $date['timestamp'];
                break;
            }
        }
        if (empty($openansweringdate)) {
            return new overviewitem(
                name: get_string('answeringopen', 'studentquiz'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($openansweringdate);

        return new overviewitem(
            name: get_string('answeringopen', 'studentquiz'),
            value: $openansweringdate,
            content: $content,
        );
    }

    /**
     * Get the answering closes date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_closeanswering(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $closeansweringdate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timecloseanswering') {
                $closeansweringdate = $date['timestamp'];
                break;
            }
        }
        if (empty($closeansweringdate)) {
            return new overviewitem(
                name: get_string('answeringclose', 'studentquiz'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($closeansweringdate);

        return new overviewitem(
            name: get_string('answeringclose', 'studentquiz'),
            value: $closeansweringdate,
            content: $content,
        );
    }
}
