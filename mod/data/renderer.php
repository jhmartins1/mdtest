<?php

defined('MOODLE_INTERNAL') || die();

class mod_data_renderer extends plugin_renderer_base {

    public function import_setting_mappings($datamodule, data_preset_importer $importer) {

        $strblank = get_string('blank', 'data');
        $strcontinue = get_string('continue');
        $strwarning = get_string('mappingwarning', 'data');
        $strfieldmappings = get_string('fieldmappings', 'data');
        $strnew = get_string('new');


        $params = $importer->get_preset_settings();
        $settings = $params->settings;
        $newfields = $params->importfields;
        $currentfields = $params->currentfields;

        $html  = html_writer::start_tag('div', array('class'=>'presetmapping'));
        $html .= html_writer::start_tag('form', array('method'=>'post', 'action'=>''));
        $html .= html_writer::start_tag('div');
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'finishimport'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'d', 'value'=>$datamodule->id));

        if ($importer instanceof data_preset_existing_importer) {
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'fullname', 'value'=>$importer->get_userid().'/'.$importer->get_directory()));
        } else {
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'directory', 'value'=>$importer->get_directory()));
        }

        if (!empty($newfields)) {
            $html .= $this->output->heading_with_help($strfieldmappings, 'fieldmappings', 'data', '', '', 3);

            $table = new html_table();
            $table->data = array();

            foreach ($newfields as $nid => $newfield) {
                $row = array();
                $row[0] = html_writer::tag('label', $newfield->name, array('for'=>'id_'.$newfield->name));
                $attrs = array('name' => 'field_' . $nid, 'id' => 'id_' . $newfield->name, 'class' => 'custom-select');
                $row[1] = html_writer::start_tag('select', $attrs);

                $selected = false;
                foreach ($currentfields as $cid => $currentfield) {
                    if ($currentfield->type != $newfield->type) {
                        continue;
                    }
                    if ($currentfield->name == $newfield->name) {
                        $row[1] .= html_writer::tag('option', get_string('mapexistingfield', 'data', $currentfield->name), array('value'=>$cid, 'selected'=>'selected'));
                        $selected=true;
                    } else {
                        $row[1] .= html_writer::tag('option', get_string('mapexistingfield', 'data', $currentfield->name), array('value'=>$cid));
                    }
                }

                if ($selected) {
                    $row[1] .= html_writer::tag('option', get_string('mapnewfield', 'data'), array('value'=>'-1'));
                } else {
                    $row[1] .= html_writer::tag('option', get_string('mapnewfield', 'data'), array('value'=>'-1', 'selected'=>'selected'));
                }

                $row[1] .= html_writer::end_tag('select');
                $table->data[] = $row;
            }
            $html .= html_writer::table($table);
            $html .= html_writer::tag('p', $strwarning);
        } else {
            $html .= $this->output->notification(get_string('nodefinedfields', 'data'));
        }

        $html .= html_writer::start_tag('div', array('class'=>'overwritesettings'));
        $html .= html_writer::tag('label', get_string('overwritesettings', 'data'), array('for' => 'overwritesettings'));
        $attrs = array('type' => 'checkbox', 'name' => 'overwritesettings', 'id' => 'overwritesettings', 'class' => 'ml-1');
        $html .= html_writer::empty_tag('input', $attrs);
        $html .= html_writer::end_tag('div');
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'btn btn-primary', 'value' => $strcontinue));

        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * Renders the action bar for the field page.
     *
     * @param \mod_data\output\fields_action_bar $actionbar
     * @return string The HTML output
     */
    public function render_fields_action_bar(\mod_data\output\fields_action_bar $actionbar): string {
        $data = $actionbar->export_for_template($this);
        return $this->render_from_template('mod_data/fields_action_bar', $data);
    }

    /**
     * Renders the action bar for the view page.
     *
     * @param \mod_data\output\view_action_bar $actionbar
     * @return string The HTML output
     */
    public function render_view_action_bar(\mod_data\output\view_action_bar $actionbar): string {
        $data = $actionbar->export_for_template($this);
        return $this->render_from_template('mod_data/view_action_bar', $data);
    }

    /**
     * Renders the action bar for the template page.
     *
     * @param \mod_data\output\templates_action_bar $actionbar
     * @return string The HTML output
     */
    public function render_templates_action_bar(\mod_data\output\templates_action_bar $actionbar): string {
        $data = $actionbar->export_for_template($this);
        return $this->render_from_template('mod_data/templates_action_bar', $data);
    }

    /**
     * Renders the action bar for the preset page.
     *
     * @param \mod_data\output\presets_action_bar $actionbar
     * @return string The HTML output
     */
    public function render_presets_action_bar(\mod_data\output\presets_action_bar $actionbar): string {
        $data = $actionbar->export_for_template($this);
        return $this->render_from_template('mod_data/presets_action_bar', $data);
    }

    /**
     * Renders the presets table in the preset page.
     *
     * @param \mod_data\output\presets $presets
     * @return string The HTML output
     */
    public function render_presets(\mod_data\output\presets $presets): string {
        $data = $presets->export_for_template($this);
        return $this->render_from_template('mod_data/presets', $data);
    }

    /**
     * Renders the action bar for the zero state (no fields created) page.
     *
     * @param \mod_data\manager $manager The manager instance.
     *
     * @return string The HTML output
     */
    public function render_zero_state(\mod_data\manager $manager): string {
        $actionbar = new \mod_data\output\zero_state_action_bar($manager);
        $data = $actionbar->export_for_template($this);
        if (empty($data)) {
            // No actions for the user.
            $data['title'] = get_string('activitynotready');
            $data['intro'] = get_string('comebacklater');
        } else {
            $data['title'] = get_string('startbuilding', 'mod_data');
            $data['intro'] = get_string('createfields', 'mod_data');
        }
        $data['noitemsimgurl'] = $this->output->image_url('nofields', 'mod_data')->out();

        return $this->render_from_template('mod_data/zero_state', $data);
    }

    /**
     * Renders the action bar for an empty database view page.
     *
     * @param \mod_data\manager $manager The manager instance.
     *
     * @return string The HTML output
     */
    public function render_empty_database(\mod_data\manager $manager): string {
        $actionbar = new \mod_data\output\empty_database_action_bar($manager);
        $data = $actionbar->export_for_template($this);
        $data['noitemsimgurl'] = $this->output->image_url('nofields', 'mod_data')->out();

        return $this->render_from_template('mod_data/view_noentries', $data);
    }
}
