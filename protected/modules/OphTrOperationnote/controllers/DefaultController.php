<?php

class DefaultController extends BaseEventTypeController
{
	public $surgeons;
	public $selected_procedures = array();
	public $eye;
	public $drugs;
	public $anaesthetic_hidden;

	public function getDefaultElements($action, $event_type_id=false, $event=false) {
		$elements = parent::getDefaultElements($action, $event_type_id, $event);

		// If we're loading the create form and there are procedures pulled from the booking which map to elements
		// then we need to include them in the form
		if ($action == 'create' && empty($_POST)) {
			$extra_elements = array();

			$new_elements = array(array_shift($elements));

			foreach ($this->selected_procedures as $procedure) {
				$criteria = new CDbCriteria;
				$criteria->compare('procedure_id',$procedure->id);
				$criteria->order = 'display_order asc';

				foreach (ProcedureListOperationElement::model()->findAll($criteria) as $element) {
					$element = new $element->element_type->class_name;

					if (!in_array(get_class($element),$extra_elements)) {
						$extra_elements[] = get_class($element);
						$new_elements[] = $element;
					}
				}
			}

			$elements = array_merge($new_elements, $elements);
		}

		return $elements;
	}

	public function actionCreate() {
		$criteria = new CDbCriteria;
		$criteria->compare('is_doctor',1);
		$criteria->order = 'first_name,last_name asc';

		$this->surgeons = User::model()->findAll($criteria);

		// Get the procedure list and eye from the most recent booking for the episode of the current user's subspecialty
		if (!$patient = Patient::model()->findByPk(@$_GET['patient_id'])) {
			throw new SystemException('Patient not found: '.@$_GET['patient_id']);
		}

		if ($episode = $patient->getEpisodeForCurrentSubspecialty()) {
			if ($booking = $episode->getMostRecentBooking()) {
				foreach ($booking->elementOperation->procedures as $procedure) {
					$this->selected_procedures[] = $procedure;
				}

				$this->eye = $booking->elementOperation->eye;
			}
		}

		$this->drugs = $this->getDrugsBySiteAndSubspecialty();
		$this->anaesthetic_hidden = (@$_POST['ElementAnaesthetic']['anaesthetic_type_id'] == 5);

		parent::actionCreate();
	}

	public function actionUpdate($id) {
		$criteria = new CDbCriteria;
		$criteria->compare('is_doctor',1);
		$criteria->order = 'first_name,last_name asc';

		$this->surgeons = User::model()->findAll($criteria);

		$this->drugs = $this->getDrugsBySiteAndSubspecialty();

		if (empty($_POST)) {
			$anaesthetic_element = ElementAnaesthetic::model()->find('event_id=?',array($id));
			$this->anaesthetic_hidden = ($anaesthetic_element->anaesthetic_type_id == 5);
		} else {
			$this->anaesthetic_hidden = (@$_POST['ElementAnaesthetic']['anaesthetic_type_id'] == 5);
		}

		parent::actionUpdate($id);
	}

	public function actionView($id) {
		$pl = ElementProcedureList::model()->find('event_id=?',array($id));

		$this->eye = ($pl->eye_id == 1 ? 'L' : 'R');

		parent::actionView($id);
	}

	public function getDrugsBySiteAndSubspecialty() {
		$firm = Firm::model()->findByPk(Yii::app()->session['selected_firm_id']);
		$subspecialty_id = $firm->serviceSubspecialtyAssignment->subspecialty_id;
		$site_id = Yii::app()->request->cookies['site_id']->value;

		return CHtml::listData(Yii::app()->db->createCommand()
			->select('drug.id, drug.name')
			->from('drug')
			->join('site_subspecialty_drug','site_subspecialty_drug.drug_id = drug.id')
			->where('site_subspecialty_drug.subspecialty_id = :subSpecialtyId and site_subspecialty_drug.site_id = :siteId',array(':subSpecialtyId'=>$subspecialty_id,':siteId'=>$site_id))
			->queryAll(), 'id', 'name');
	}

	public function actionLoadElementByProcedure() {
		if (!$proc = Procedure::model()->findByPk((integer)@$_GET['procedure_id'])) {
			throw new SystemException('Procedure not found: '.@$_GET['procedure_id']);
		}

		$form = new BaseEventTypeCActiveForm;

		$criteria = new CDbCriteria;
		$criteria->compare('procedure_id',$proc->id);
		$criteria->order = 'display_order asc';

		foreach (ProcedureListOperationElement::model()->findAll($criteria) as $element) {
			$element = new $element->element_type->class_name;

			$this->eye = Eye::model()->findByPk(@$_GET['eye']);

			$this->renderPartial(
				'create' . '_' . get_class($element),
				array('element' => $element, 'data' => array(), 'form' => $form, 'ondemand' => true),
				false, true
			);
		}
	}

	public function actionGetElementsToDelete() {
		if (!$proc = Procedure::model()->findByPk((integer)@$_POST['procedure_id'])) {
			throw new SystemException('Procedure not found: '.@$_POST['procedure_id']);
		}

		$criteria = new CDbCriteria;
		$criteria->compare('procedure_id',$proc->id);
		$criteria->order = 'display_order asc';

		if (@$_POST['remaining_procedures']) {
			$procedures = explode(',',$_POST['remaining_procedures']);
		} else {
			$procedures = array();
		}

		$elements = array();

		foreach (ProcedureListOperationElement::model()->findAll($criteria) as $element) {
			if (empty($procedures) || !ProcedureListOperationElement::model()->find('procedure_id in ('.implode(',',$procedures).') and element_type_id = '.$element->element_type->id)) {
				$elements[] = $element->element_type->class_name;
			}
		}

		die(json_encode($elements));
	}
}