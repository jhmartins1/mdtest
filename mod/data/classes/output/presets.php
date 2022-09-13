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

namespace mod_data\output;

use action_menu;
use action_menu_link_secondary;
use mod_data\preset;
use moodle_url;
use templatable;
use renderable;
use renderer_base;
use stdClass;

/**
 * Renderable class for the presets table in the database activity.
 *
 * @package    mod_data
 * @copyright  2021 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presets implements templatable, renderable {

    /** @var int $id The database module id. */
    private $id;

    /** @var array $presets The array containing the existing presets. */
    private $presets;

    /** @var moodle_url $formactionurl The the action url for the form. */
    private $formactionurl;

    /** @var bool $manage Whether the manage preset options should be displayed. */
    private $manage;

    /**
     * The class constructor.
     *
     * @param int $id The database module id
     * @param array $presets The array containing the existing presets
     * @param moodle_url $formactionurl The the action url for the form
     * @param bool $manage Whether the manage preset options should be displayed
     */
    public function __construct(int $id, array $presets, moodle_url $formactionurl, bool $manage = false) {
        $this->id = $id;
        $this->presets = $presets;
        $this->formactionurl = $formactionurl;
        $this->manage = $manage;
    }

    /**
     * Export the data for the mustache template.
     *
     * @param renderer_base $output The renderer to be used to render the action bar elements.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {

        $presets = $this->get_presets($output);
        return [
            'd' => $this->id,
            'formactionul' => $this->formactionurl->out(),
            'showmanage' => $this->manage,
            'presets' => $presets,
        ];
    }

    /**
     * Returns the presets list with the information required to display them.
     *
     * @param renderer_base $output The renderer to be used to render the action bar elements.
     * @return array Presets list.
     */
    private function get_presets(renderer_base $output): array {
        $presets = [];
        foreach ($this->presets as $preset) {
            $presetname = $preset->name;
            $userid = $preset instanceof preset ? $preset->get_userid() : $preset->userid;
            if (!empty($userid)) {
                // If the preset has the userid field, the full name of creator it will be added to the end of the name.
                $userfieldsapi = \core_user\fields::for_name();
                $namefields = $userfieldsapi->get_sql('', false, '', '', false)->selects;
                $fields = 'id, ' . $namefields;
                $presetuser = \core_user::get_user($userid, $fields, MUST_EXIST);
                $username = fullname($presetuser, true);
                $presetname = "{$presetname} ({$username})";
            }
            $actions = $this->get_preset_action_menu($output, $preset, $userid);

            $presets[] = [
                'id' => $this->id,
                'name' => $preset->name,
                'shortname' => $preset->shortname,
                'fullname' => $presetname,
                'description' => $preset->description,
                'userid' => $userid,
                'actions' => $actions,
            ];
        }

        return $presets;
    }

    /**
     * Return the preset action menu data.
     *
     * @param renderer_base $output The renderer to be used to render the action bar elements.
     * @param preset|stdClass $preset the preset object
     * @param int|null $userid the user id (null for plugin presets)
     * @return stdClass the resulting action menu
     */
    private function get_preset_action_menu(renderer_base $output, $preset, ?int $userid): stdClass {
        global $PAGE;

        $actions = new stdClass();
        $actionmenu = null;
        // Only presets saved by users can be edited or removed (so the datapreset plugins shouldn't display these buttons).
        if ($this->manage && !$preset->isplugin) {
            $actionmenu = new action_menu();
            $icon = $output->pix_icon('i/menu', get_string('actions'));
            $actionmenu->set_menu_trigger($icon, 'btn btn-icon d-flex align-items-center justify-content-center');
            $actionmenu->set_action_label(get_string('actions'));
            $actionmenu->attributes['class'] .= ' presets-actions';

            // Only users with mod/data:manageuserpresets capability have options to edit the preset.
            if ($preset->can_manage()) {
                $params = [
                    'd' => $this->id,
                    'action' => 'edit',
                ];
                $editactionurl = new moodle_url('/mod/data/preset.php', $params);
                $attributes = [
                    'data-action' => 'editpreset',
                    'data-dataid' => $this->id,
                    "data-presetname" => $preset->name,
                    "data-presetdescription" => $preset->description,
                ];
                $actionmenu->add(new action_menu_link_secondary(
                    $editactionurl,
                    null,
                    get_string('edit'),
                    $attributes
                ));

                // Delete.
                $params = [
                    'd' => $this->id,
                    'action' => 'delete',
                ];
                $deleteactionurl = new moodle_url('/mod/data/preset.php', $params);
                $attributes = [
                    'data-action' => 'deletepreset',
                    'data-dataid' => $this->id,
                    "data-presetname" => $preset->name,
                ];
                $actionmenu->add(new action_menu_link_secondary(
                    $deleteactionurl,
                    null,
                    get_string('delete'),
                    $attributes,
                ));
            }
        }

        if (!is_null($actionmenu)) {
            $actions = $actionmenu->export_for_template($output);
        }

        return $actions;
    }
}
