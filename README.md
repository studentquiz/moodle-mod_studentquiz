# ![StudentQuiz](https://raw.githubusercontent.com/studentquiz/moodle-mod_studentquiz/master/pix/icon.svg?sanitize=true) StudentQuiz

[![Latest Release](https://img.shields.io/github/v/release/studentquiz/moodle-mod_studentquiz?sort=semver&color=orange)](https://github.com/studentquiz/moodle-mod_studentquiz/releases)
[![Build Status](https://github.com/studentquiz/moodle-mod_studentquiz/workflows/Moodle%20Plugin%20CI/badge.svg?branch=master)](https://github.com/studentquiz/moodle-mod_studentquiz/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3Amaster)
[![PHP Support](https://img.shields.io/badge/php-7.2_--_8.0-blue)](https://github.com/studentquiz/moodle-mod_studentquiz/actions)
[![Moodle Support](https://img.shields.io/badge/Moodle-%3E%3D%203.9 -- 3.11-blue)](https://github.com/studentquiz/moodle-mod_studentquiz/actions)
[![License GPL-3.0](https://img.shields.io/github/license/studentquiz/moodle-mod_studentquiz?color=lightgrey)](https://github.com/studentquiz/moodle-mod_studentquiz/blob/master/LICENSE)
[![GitHub contributors](https://img.shields.io/github/contributors/studentquiz/moodle-mod_studentquiz)](https://github.com/studentquiz/moodle-mod_studentquiz/graphs/contributors)

Students like self-assessments in order to prepare for exams. However, it’s hard to provide all the many questions
needed. That’s one reason why the [University of Applied Sciences Rapperswil](https://www.hsr.ch/de/) developed the
Moodle plugin StudentQuiz. StudentQuiz enables students to collaboratively create their own question pools within
Moodle. Even if an individual student contributes a few questions only, a large cohort could easily build up an
extensive question pool.

In StudentQuiz, students can filter questions into quizzes, and they can rate and comment on questions while working
through the quizzes. StudentQuiz collects usage data for each question and ranks students based on their contribution
and performance within the quizzes. A personal learning assistance feature displays the individual progress and compares
it with the community average. The created questions become part of the Moodle question bank and can be reused in other
Moodle quizzes.

A teacher can configure:

- whether StudentQuiz runs anonymously or displays student names.
- whether students can rate and comment on questions.
- what questions types are allowed to be added to the pool.
- the number of points assigned to questions contributed and answers given.

There are more benefits of using StudentQuiz. Find out in our 15 minutes [introduction video](https://tube.switch.ch/videos/33da1b63).

## Installation

Download StudentQuiz from the [Moodle Plugin Directory](https://moodle.org/plugins/mod_studentquiz) and install by going
to the *Site administration -> Plugins -> Install plugins* page. You can try StudentQuiz without installing on the
[StudentQuiz Demo Page](http://studentquiz.hsr.ch/).

## Upgrade

For changes and instructions please read the [Release Notes](https://github.com/studentquiz/moodle-mod_studentquiz/releases).

## Documentation

You can find manuals for each role in the [manuals website](https://docs.moodle.org/38/en/StudentQuiz_module).

## Compatibility

Supported and tested with:

- Moodle 3.9, 3.10, 3.11 - Warning! this version does not work with Moodle 4.0. A compatible version is being developed.
- PHP 7.2, 7.3, 7.4, 8.0
- Databases: MySQL, MariaDB, PostgreSQL, SQL Server 2017 (experimental)
- Browsers: Firefox, Chrome, Safari, Edge

Refer to the Moodle release notes for the minimum requirements for PHP and the databases. Other modern browsers should
be compatible too, it's just not tested or developed against them explicitly.
SQL Server support is only experimental, as we have [no environment to run automated tests on it yet](https://github.com/moodlehq/moodle-plugin-ci/issues/92). It can break on any minor update and we rely on feedback and input from the community.

## Contributing

Please help translate StudentQuiz into your language on [AMOS](https://lang.moodle.org/local/amos/).

Feel free to submit code changes as [Pull Request](https://github.com/studentquiz/moodle-mod_studentquiz/pulls) or help
people and universities around the world in our [Issue Tracker](https://github.com/studentquiz/moodle-mod_studentquiz/issues).

## License

[GNU General Public License v3.0](https://github.com/studentquiz/moodle-mod_studentquiz/blob/master/LICENSE)

(c) [Hochschule für Technik Rapperswil](https://www.hsr.ch/)
(c) [The Open University](https://www.open.ac.uk/)
