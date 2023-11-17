# Change log for the StudentQuiz activity

## Version 5.2.0

Mainly a bugfix release, but with one new feature adding activity completion.

This version works with Moodle 4.0, 4.1 and 4.2. (4.3 support is being worked on.)

* New feature: You can now set automatic completion rules like 'Students must author at least 3 questions' or
  'Students must score at least 42 points'.
* An improvement to set the state of the question in the Moodle question bank (draft/read) based on the state of the question in StudentQuiz.
* New actvity icon to match the Moodle 4.x style.
* Fix to ensure that only the right notifications are sent when using separate groups mode.
* Fix to permission checks on questions in separate groups mode. 
* Fix display order of the question list, including pinned questions.
* Fix an issue that prevented the use of question types where the student responds by uploading a file.
* Fix backup and restore of availability information.
* Fix questions sometimes being incorrectly set to state Disapproved. 
* Fix the wording of one of the notifications.
* Fix to the 'delete orphaned questions' task.
* Fix the layout of some buttons in Moodle 4.2.
* Technical fixes (automated testing config, coding style, improved tests for backup and restore).


## Version 5.1.0

This is basically a bugfix release. Also, we can confirm it works with Moodle 4.1 as well as 4.0.

* Show the question version number in StudentQuiz.
* ... but don't show the Status column. We don't want to expose students to the concept of draft questions.
* Improve validation when editing StudentQuiz settings.
* Fix an issue where StudentQuiz events did not show in the calendar/timeline.
* Fix a bug where names were swapped in emails about anonymous ranking.
* Improvement to make the 3.x -> 4.x upgrade more robust.


## Version 5.0.0

* The focus of this release was getting the plugin to work again after the Moodle 4.0 question bank changes.


## Older releases

Where not documented here. See the releases on GitHub.
https://github.com/studentquiz/moodle-mod_studentquiz/releases
