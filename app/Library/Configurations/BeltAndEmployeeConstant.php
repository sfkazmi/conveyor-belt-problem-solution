<?php

namespace App\Library\Configurations;

class BeltAndEmployeeConstant
{
    const NUMBER_OF_WORKERS = 6;
    const BELT_ITERATIONS = 100;
    const STEPS_IN_BELT = 3;

    const EMPLOYEE_INTEGRATION = [
        'arms' => [false, false, false, false],
        'names' => ['EmployeeOne', 'EmployeeTwo', 'EmployeeThree', 'EmployeeFour', 'EmployeeFive', 'EmployeeSix'],
        'collectable_items' => ['A', 'B'], // What the worker wants to pick up
        'items_to_process' => ['A', 'B'], // What the worker needs to process an item
        'do_not_collect' => ['PROCESSED'], // What a worker doesnt want to collect
        'processed_item' => 'PROCESSED', // What gets produced by a worker
        'increment_time' => 4, // How long it takes to process an item
        'define_blank_space' => 'EMPTY' // Define what a worker thinks is a blank space
    ];

    const COMPONENTS = ['A', 'B', 'EMPTY'];
}
