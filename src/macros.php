<?php
	
//add some special fields to the illuminate/form
Form::macro('date', function($name, $value = null, $options = array()) {
    $input =  '<input type="date" name="' . $name . '" value="' . $value . '"';

    foreach ($options as $key => $value) {
        $input .= ' ' . $key . '="' . $value . '"';
    }

    $input .= '>';

    return $input;
});

Form::macro('datetime', function($name, $value = null, $options = array()) {
    $input =  '<input type="datetime" name="' . $name . '" value="' . $value . '"';

    foreach ($options as $key => $value) {
        $input .= ' ' . $key . '="' . $value . '"';
    }

    $input .= '>';

    return $input;
});

Form::macro('decimal', function($name, $value = null, $options = array()) {
    $input =  '<input type="number" step="0.01" name="' . $name . '" value="' . $value . '"';

    foreach ($options as $key => $value) {
        $input .= ' ' . $key . '="' . $value . '"';
    }

    $input .= '>';

    return $input;
});

Form::macro('integer', function($name, $value = null, $options = array()) {
    $input =  '<input type="number" step="1" name="' . $name . '" value="' . $value . '"';

    foreach ($options as $key => $value) {
        $input .= ' ' . $key . '="' . $value . '"';
    }

    $input .= '>';

    return $input;
});

# Currently not using; interferes with Chrome implementation of datetimepicker
/*Form::macro('time', function($name, $value = null, $options = array()) {
    $input =  '<input type="time" name="' . $name . '" value="' . $value . '"';

    foreach ($options as $key => $value) {
        $input .= ' ' . $key . '="' . $value . '"';
    }

    $input .= '>';

    return $input;
});*/
