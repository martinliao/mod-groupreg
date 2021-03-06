<?php

	require_once("../../config.php");
    require_once("lib.php");

    $id         = required_param('id', PARAM_INT);
	$action     = isset($_POST['action']) ? $_POST['action'] : false;
	
	$url = new moodle_url('/mod/groupreg/export.php', array('id'=>$id));
    if ($action !== '') {
        $url->param('action', $action);
    }
	$PAGE->set_url($url);
		
	if (! $cm = get_coursemodule_from_id('groupreg', $id)) {
        print_error("invalidcoursemodule");
    }
    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error("coursemisconf");
    }
    
    require_login($course->id, false, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/groupreg:performassignment', $context);
	
	if (!$choice = groupreg_get_groupreg($cm->instance)) {
        print_error('invalidcoursemodule');
    }	
	
	if ($action && $action == 'download-csv') {
		
		$unique_user = optional_param('unique-user', 'false', PARAM_ALPHA);
		
		//
		// Prepare CSV output
		//
		add_to_log($course->id, "groupreg", "report download", "report.php?id=$cm->id", "$choice->id",$cm->id);
		$filename = clean_filename("$course->shortname ".strip_tags(format_string($choice->name,true))).'.csv';

		/// Print header to force download
		if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
			@header('Cache-Control: max-age=10');
			@header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
			@header('Pragma: ');
		} else { //normal http - prevent caching at all cost
			@header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
			@header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
			@header('Pragma: no-cache');
		}
		header("Content-Type: application/download\n");
		header("Content-Disposition: attachment; filename=\"$filename\"");
    
		$csv = '';
		
		//
		// fetch data from DB
		//
		$single_groups = array(); // groups with no grouping, to be listed separately
		$alldata = $DB->get_records_sql('SELECT
										a.id,
										u.id as userid,
										u.lastname,
										u.firstname,
										g.name,
										o.grouping,
										a.preference,
										a.usergroup
									FROM
										{user} u,
										{groups} g,
										{groupreg_answers} a,
										{groupreg_options} o
									WHERE
										a.groupregid = ? AND
										o.id = a.optionid AND
										g.id = o.text AND
										u.id = a.userid
									ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC, a.preference DESC', array($choice->id));
									
		$userdata = array();
		$c= -1;
		$lastuserid = -1;
		$usergroups = array();
		$userassigned = array();
		foreach($alldata as $data) {
			// go to next user if the ID has changed
			if ($data->userid != $lastuserid)
				$c++;
			
			// if no data for this user, initiate basic data from this line
			if (!isset($userdata[$c])) {
				$lastuserid = $data->userid;
				$userdata[$c] = new stdClass;
				$userdata[$c]->id = $data->userid;
				$userdata[$c]->firstname = $data->firstname;
				$userdata[$c]->lastname = $data->lastname;
				$userdata[$c]->usergroup = $data->usergroup;
				$userdata[$c]->answers = array();
				
				// check usergroup data
				if (!isset($usergroups[$data->usergroup]))
					$usergroups[$data->usergroup] = array();
				$usergroups[$data->usergroup][] = $data->lastname.', '.$data->firstname;
			}
			
			// write data about the current group and grouping choices
			$userdata[$c]->answers['grouping: '.$data->grouping] = $data->preference;
			$userdata[$c]->answers['group: '.$data->name] = $data->preference;
			
			// if the current group has no grouping associated, remember
			if ($data->grouping == '' && !in_array($data->name, $single_groups))
				$single_groups[] = $data->name;
		}
		
		if ($choice->assigned) {
			// fetch the completed assignment data from DB
			$assigned = $DB->get_records_sql('SELECT a.userid, g.name, o.grouping 
												FROM {groupreg_assigned} a, {groups} g, {groupreg_options} o
												WHERE a.groupregid = ? AND o.id = a.optionid AND g.id = o.text
												ORDER BY a.userid ASC', array($choice->id));
			foreach ($assigned as $assignment) {
				$userassigned[$assignment->userid] = new stdClass;
				$userassigned[$assignment->userid]->name = $assignment->name;
				$userassigned[$assignment->userid]->grouping = $assignment->grouping;
			}
		}
		
		//
		// Fetch more data for header
		//
		$eq_classes = $DB->get_records_sql('SELECT
												DISTINCT o.grouping
											FROM
												{groupreg_options} o,
												{groups} g
											WHERE
												o.groupregid = ? AND
												g.id = o.text AND
												o.grouping <> \'\'
											ORDER BY g.id', array($choice->id));
		
		//
		// Build and output header row
		//
		$headerRow = array();
		
		$headerRow[] = get_string('export_header_name', 'groupreg');
		
		$headerRow[] = get_string('export_header_groupsize', 'groupreg');
		for ($i = 1; $i < $choice->groupmembers; $i++)
			$headerRow[] = get_string('export_header_groupmember_n', 'groupreg', $i);
			
		foreach($eq_classes as $class)
			$headerRow[] = '"'.get_string('export_header_grouping', 'groupreg', $class->grouping).'"';
		foreach($single_groups as $group)
			$headerRow[] = '"'.$group.'"';
			
		if ($choice->assigned) {
			$headerRow[] = get_string('export_header_assigned_group', 'groupreg');
		}
		
		$csv .= implode(';', $headerRow)."\n";
		
		//
		// Build and output data rows
		//
		
		$usersShown = array();
		
		foreach ($userdata as $user) {
			
			$row = array();
			
			// name
			$fullname = $user->lastname.', '.$user->firstname;
			$row[] = '"'.$fullname.'"';
			
			if ($unique_user == 'true' && in_array($fullname, $usersShown))
				continue;
				
			$usersShown[] = $fullname;
						
			// usergroup size and members
			$row[] = sizeof($usergroups[$user->usergroup]);
			$displayedgroupmembers = 0;
			for ($i = 0; $i < $choice->groupmembers; $i++)
				if (isset($usergroups[$user->usergroup][$i]) && $fullname != $usergroups[$user->usergroup][$i]) {
					$row[] = '"'.$usergroups[$user->usergroup][$i].'"';	
					$displayedgroupmembers++;
					$usersShown[] = $usergroups[$user->usergroup][$i];
				}
				
			for ($i = $displayedgroupmembers; $i < $choice->groupmembers-1; $i++)
				$row[] = '';
					
			// preferences for groupings
			foreach($eq_classes as $class) {
				if (isset($user->answers['grouping: '.$class->grouping])) {
					$pref = $user->answers['grouping: '.$class->grouping];
					$output = $pref > 0 ? $pref : 'N';
					if ($choice->assigned && isset($userassigned[$user->id]) && $userassigned[$user->id]->grouping == $class->grouping)
						$output .= 'X';
					$row[] = $output;
				} else
					$row[] = '';
			}
			foreach($single_groups as $group) {
				if (isset($user->answers['group: '.$group])) {
					$pref = $user->answers['group: '.$group];
					$output = $pref > 0 ? $pref : 'N';
					if ($choice->assigned && isset($userassigned[$user->id]) && $userassigned[$user->id]->name == $group)
						$output .= 'X';
					$row[] = $output;
				} else
					$row[] = '';
			}
				
			// assigned group
			if ($choice->assigned) {
				if (isset($userassigned[$user->id]))
					$row[] = '"'.$userassigned[$user->id]->name.'"';
				else
					$row[] = '-';
			}
			
			$csv .= implode(';', $row)."\n";
			
		}
		
		//echo iconv("UTF-8", "ISO-8859-1", $csv);		
		echo $csv;
		
	} else {
	
		// Output the confirmation form
	
		$PAGE->set_title(format_string($choice->name).': '.get_string('exportassignment', 'groupreg'));
		$PAGE->set_heading($course->fullname);
		echo $OUTPUT->header();
		
		$renderer = $PAGE->get_renderer('mod_groupreg');
		
		echo $renderer->display_export_assignment_form($cm);
		
		echo $OUTPUT->footer();
		
	}