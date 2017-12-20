Upgrades
========

This guides lists the various scenarios every developmer should keep in mind 
when upgrading StudentQuiz.

The Version of Moodle Core defines what options and functions are available to 
the StudentQuiz installed. 

Check https://moodle.org/plugins/mod_studentquiz to see the list of 
supported Moodle Versions.

During the development history of StudentQuiz, several releases were published. 
The automatic Plugin Update process ensures every Plugin is updated 
as soon as an administrator has logged in and confirmed the update process.

The moodle core only guarantees the various database migrations defined in 
upgrade.php are executed in order. There are some specific scenarios, 
that are not directly covered by moodle core functionality: 

Note: We only support made with moodle version 3.0 or older, 
because StudentQuiz was never installed on a moodle version < 3.0. 

Scenario: Restore from with older Backup
----------------------------------------
Teacher A has created a course with moodle
Version 3.2 and Plugin Version 2.00. 
In Summer Moodle was upgraded to 3.3 and 
Student Quiz Plugin version 2.0.4.
He now wants to reuse the course with the same structure 
for his new class. He therefore creates a backup of 
last years course and imports it directly into a new course.
--
How to test: 
Install Moodle 3.0 With StudentQuiz Plugin Version 2.0
Prevent Moodle from Performing any updates on its core or on StudentQuiz Plugin.
Generate a Course, add one or two StudentQuiz activities
Enrol students, fake some student activity by adding a few questions 
and create at least two different Quiz executions for each students
(different by the ids of the question selected). 
Then backup the course to create the moodle-2 backup file mbz. 

Now upgrade Moodle Core and the StudentQuiz Plugin to their newest 
Versions. 

No restore the course you backed up before into a new course
and check the following invariants: 

- All questions are still here. 
- All Quiz Executions got copied.
- All settings are the same as before.
















