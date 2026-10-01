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

namespace enrol_autoenrol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/autoenrol/lib.php');

/**
 * Tests for the autoenrol enrolment plugin.
 *
 * @package    enrol_autoenrol
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\enrol_autoenrol_plugin::class)]
final class lib_test extends \advanced_testcase {
    /**
     * Build the submitted instance form data.
     *
     * @param int $method the "Enrol When" value
     * @return array
     */
    private function get_form_data(int $method): array {
        global $DB;
        return [
            'name' => '',
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => $DB->get_field('role', 'id', ['shortname' => 'student']),
            'enrolperiod' => 0,
            'expirynotify' => 0,
            'expirythreshold' => 86400,
            'enrolstartdate' => 0,
            'enrolenddate' => 0,
            'customint1' => $method,
            'customint3' => 0,
            'customint4' => 0,
            'customint5' => 0,
            'customint6' => 0,
            'customint7' => 0,
            'customint8' => 0,
            'customchar1' => '',
            'customchar3' => '-',
            'availabilityconditionsjson' => '',
        ];
    }

    /**
     * Users without enrol/autoenrol:method must not switch an instance to login enrolment.
     */
    #[Group('baseline')]
    public function test_edit_instance_validation_rejects_login_method_without_capability(): void {
        $this->resetAfterTest();
        set_config('loginenrol', 1, 'enrol_autoenrol');
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $context = \context_course::instance($course->id);
        $instance = (object) ['id' => null, 'courseid' => $course->id, 'customint1' => 0];
        $this->setUser($teacher);

        $errors = enrol_get_plugin('autoenrol')->edit_instance_validation($this->get_form_data(1), [], $instance, $context);

        $this->assertArrayHasKey('customint1', $errors);
    }

    /**
     * Managers may set login enrolment and teachers may keep an existing value.
     */
    #[Group('baseline')]
    public function test_edit_instance_validation_accepts_login_method_with_capability(): void {
        $this->resetAfterTest();
        set_config('loginenrol', 1, 'enrol_autoenrol');
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_and_enrol($course, 'manager');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $context = \context_course::instance($course->id);
        $plugin = enrol_get_plugin('autoenrol');

        $this->setUser($manager);
        $instance = (object) ['id' => null, 'courseid' => $course->id, 'customint1' => 0];
        $this->assertSame([], $plugin->edit_instance_validation($this->get_form_data(1), [], $instance, $context));

        $this->setUser($teacher);
        $instance = (object) ['id' => 1, 'courseid' => $course->id, 'customint1' => 1];
        $this->assertSame([], $plugin->edit_instance_validation($this->get_form_data(1), [], $instance, $context));
    }
}
