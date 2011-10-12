<?php
/*
_____________________________________________________________________________
(C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
(C) OpenEyes Foundation, 2011
This file is part of OpenEyes.
OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
_____________________________________________________________________________
http://www.openeyes.org.uk	 info@openeyes.org.uk
--
*/


if (!empty($episode)) {
	$diagnosis = $episode->getPrincipalDiagnosis();

	if (empty($diagnosis)) {
					$eye = 'No diagnosis';
					$text = 'No diagnosis';
	} else {
					$eye = $diagnosis->getEyeText();
					$text = $diagnosis->disorder->term;
	}
?>
<h3>Episode Summary (<?php echo $episode->firm->serviceSpecialtyAssignment->specialty->name?>)</h3>

<h4>Start date:</h4>
<div class="eventHighlight">
	<h4><?php echo date('jS F, Y', strtotime($episode->start_date))?></h4>
</div>

<h4>Principal eye:</h4>
<div class="eventHighlight">
	<h4><?php echo $eye?></h4>
</div>

<h4>End date:</h4>
<div class="eventHighlight">
	<h4><?php echo !empty($episode->end_date) ? $episode->end_date : '(still open)'?></h4>
</div>

<h4>Principal diagnosis:</h4>
<div class="eventHighlight">
	<h4><?php echo $text?></h4>
</div>

<h4>Specialty:</h4>
<div class="eventHighlight">
	<h4><?php echo $episode->firm->serviceSpecialtyAssignment->specialty->name?></h4>
</div>

<h4>Consultant firm:</h4>
<div class="eventHighlight">
	<h4><?php echo $episode->firm->name?></h4>
</div>
<?php
	try {
		echo $this->renderPartial(
			'/clinical/episodeSummaries/' . $episode->firm->serviceSpecialtyAssignment->specialty_id,
			array('episode' => $episode)
		);
	} catch (Exception $e) {
		// If there is no extra episode summary detail page for this specialty we don't care
	}
} else {
	// hide the episode border ?>
<script type="text/javascript">
	$('div#episodes_details').hide();
</script>
<?php
}
