<?php
/**
 * Defines message providers (types of message sent) for the studentquiz module.
 *
 * @package   mod_studentquiz
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = array(
    // Notify student that someone has edited his question. (Info to question author)
    'changed' => array(
        'capability' => 'mod/studentquiz:emailnotifychanged'
    ),
    // Notify student that someone has deleted his question. (Info to question author)
    'deleted' => array(
        'capability' => 'mod/studentquiz:emailnotifydeleted'
    ),
    // Notify student that someone has approved his question. (Info to question author)
    'approved' => array(
        'capability' => 'mod/studentquiz:emailnotifyapproved'
    ),
    // Notify student that someone has unapproved his question. (Info to question author)
    'unapproved' => array(
        'capability' => 'mod/studentquiz:emailnotifyapproved'
    ),
    // Notify student that someone has commented to his question. (Info to question author)
    'commentadded' => array(
        'capability' => 'mod/studentquiz:emailnotifycommentadded'
    ),
    // Notify student that someone has deleted their comment to his question. (Info to question author)
    'commentdeleted' => array(
        'capability' => 'mod/studentquiz:emailnotifycommentdeleted'
    ),
    // Notify student that someone has deleted his comment to someone's question. (Info to comment author)
    'minecommentdeleted' => array(
        'capability' => 'mod/studentquiz:emailnotifycommentdeleted'
    ),
);
