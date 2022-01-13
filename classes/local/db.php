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
 * Additional DB abstraction toolset.
 *
 * @package mod_studentquiz
 * @copyright 2020 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class db {

    /**
     * @var string DBFAMILY_MYSQL also counts for mariadb.
     */
    const DBFAMILY_MYSQL = 'mysql';

    /**
     * @var string DBFAMILY_POSTGRES
     */
    const DBFAMILY_POSTGRES = 'postgres';

    /**
     * @var string DBFAMILY_MSSQL
     */
    const DBFAMILY_MSSQL = 'mssql';

    /**
     * group_concat is a helper function to extend the database abstraction. It returns the function used by the current
     * selected database driver for concatenating a column when the query is grouped.
     *
     * - MySQL: GROUP_CONCAT (https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html)
     * - MariaDB: GROUP_CONCAT (https://mariadb.com/kb/en/group_concat/)
     * - PostgreSQL: https://www.postgresql.org/docs/9.0/functions-aggregate.html
     * - SQL Server: https://docs.microsoft.com/en-us/sql/t-sql/functions/string-agg-transact-sql?view=sql-server-2017
     *
     * @param string $field name
     * @return string
     */
    public static function group_concat($field) {
        global $DB;

        $family = $DB->get_dbfamily();
        switch ($family) {
            case self::DBFAMILY_MYSQL:
                return "GROUP_CONCAT($field)";
            case self::DBFAMILY_POSTGRES:
            case self::DBFAMILY_MSSQL:
                return "STRING_AGG($field, ',')";
            default:
                throw new \coding_exception("Unsupported database family: $family");
                return;
        }
    }
}
