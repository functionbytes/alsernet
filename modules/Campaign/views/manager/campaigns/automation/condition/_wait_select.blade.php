@include('helpers.form_control', [
    'type' => 'select',
    'class' => 'required',
    'label' => '',
    'name' => 'wait',
    'value' => request()->wait_amount . ' ' . request()->wait_unit . (request()->wait_amount > 1 ? 's' : ''),
    'required' => true,
    'options' => \Modules\Campaign\Models\Automation::getConditionWaitOptions(request()->wait_amount . ' ' . request()->wait_unit . (request()->wait_amount > 1 ? 's' : '')),
])
