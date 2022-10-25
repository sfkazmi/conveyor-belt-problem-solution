<?php

namespace App\Library\Services;

use App\Library\Configurations\BeltAndEmployeeConstant;
use Illuminate\Support\Facades\Log;

class ConveyorBelt
{
    /**
     * This function is starting point of the conveyor contains logic and setting configurations for conveyor and persons
     *
     * @return object
     */
    public function runConveyor()
    {
        $workerCount = BeltAndEmployeeConstant::NUMBER_OF_WORKERS; // How many workers are created
        $process_order = null;

        $stepCount = BeltAndEmployeeConstant::BELT_ITERATIONS; // How many steps to process

        $beltLength = BeltAndEmployeeConstant::STEPS_IN_BELT; // Length of Belt
        $settings = BeltAndEmployeeConstant::EMPLOYEE_INTEGRATION; // Give the workers settings
        $random = BeltAndEmployeeConstant::COMPONENTS; // Create Elements to be processed

        $belt = $this->belt($beltLength);
        $workers = $this->generateWorkers($workerCount, $settings);

        // Update the view with our Ticker
        $ticker = $this->runBelt($belt, $workers, $stepCount, $random, $process_order);

        return $this->view([
            'collection' => $ticker['collection'],
            'worker_talk' => $ticker['worker_talk']
        ]);
    }

    function view($returnView)
    {

        return (object) [
            'return_all' => $returnView['collection'],
            'log' => $returnView['worker_talk']
        ];
    }

    /**
     * Belt Calculations of steps
     *
     * @param $beltLength
     * @return array
     */
    private function belt($beltLength)
    {
        $belt = [];
        for ($x = 0; $x < $beltLength; $x++) {
            array_push($belt, null);
        }

        return $belt;
    }


    /**
     * Setting up worker settings
     *
     * @param $workerCount
     * @param $settings
     * @return array
     */
    private function generateWorkers($workerCount, $settings)
    {
        $beltid = 0;
        $workers = [];
        for ($x = 0; $x < $workerCount; $x++) {
            array_push($workers, $this->worker($x, $settings, $beltid));

            if ($x % 2 != 0) {
                $beltid++;
            }
        }

        return $workers;
    }

    /**
     * Single worker configurations
     *
     * @param $id
     * @param $settings
     * @param $beltid
     * @return object
     */
    private function worker($id, $settings, $beltid)
    {
        $object = (object) [
            'id' => $id,
            'name' => $settings['names'][$id],
            'arms' => $settings['arms'],
            'looksafter' => $beltid,
            'collectable_items' => $settings['collectable_items'], // What the worker wants to pick up
            'items_to_process' => $settings['items_to_process'], // What the worker needs to process
            'do_not_collect' => $settings['do_not_collect'], // What a worker doesnt want to collect
            'processed_item' => $settings['processed_item'], // What gets produced by a worker
            'increment_time' => $settings['increment_time'],
            'define_blank_space' => $settings['define_blank_space'],
            'increment' => 0,

        ];

        return $object;
    }


    /**
     * Running the belt logic
     *
     * @param $belt
     * @param $workers
     * @param $stepCount
     * @param $random
     * @param $process_order
     * @return array[]
     */
    private function runBelt($belt, $workers, $stepCount, $random, $process_order = null)
    {

        $created_log = [];
        $collection = [];
        $worker_talk = [];

        for ($i = 0; $i < $stepCount; $i++) {

            // Move belt, and update values
            $moveBelt = $this->move($belt, $random, $i, $process_order);
            $belt = $moveBelt['belt'];

            // Push Collection + What objects have been created
            array_push($created_log, $moveBelt['created']);
            array_push($collection, $moveBelt['removed']);

            if (isset($moveBelt['removed']->inventory)) {
                if ($moveBelt['removed']->inventory == 'c') {
                    array_push($worker_talk, [['A finished item has dropped of the conveyer']]);
                }
            };

            // Process the worker, Update Belt + Workers
            $processWorkers = $this->process($workers, $belt);
            $belt = $processWorkers['belt'];
            $workers = $processWorkers['workers'];
            array_push($worker_talk, $processWorkers['worker_talk']);
        }


        return ['collection' => $collection, 'worker_talk' => $worker_talk];
    }


    /**
     * Generating items and running belt and workers together to start production
     *
     * @param $workers
     * @param $belt
     * @return array
     */
    private function process($workers, $belt)
    {

        $worker_talk = [];

        // Reset belt to free it up to tasks
        for ($i = 0; $i < count($belt); $i++) {
            if ($belt[$workers[$i]->looksafter]) {
                $belt[$workers[$i]->looksafter]->status = 0;
            }
        }

        // Process all Workers at current Step
        for ($i = 0; $i < count($workers); $i++) {

            if ($belt[$workers[$i]->looksafter]) {

                // See whats on the belt
                $workerToBelt = $belt[$workers[$i]->looksafter];

                // Only check if both conditions are met
                if ($workerToBelt->status == 1 &&  $workers[$i]->increment > 1) {
                    // if the belt is busy then continue
                    continue;
                }

                // Process Arm Collection
                for ($x = 0; $x < count($workers[$i]->arms); $x++) {

                    // If arm has an item OR the belt has no items
                    if ($workers[$i]->arms[$x] == true || $workerToBelt->inventory == $workers[$i]->define_blank_space) {
                        continue;
                    }

                    // If collectable items is already in the arms, then don't add it to the arms
                    if (in_array($belt[$workers[$i]->looksafter]->inventory, $workers[$i]->arms)) {
                        continue;
                    }

                    // If item not to collect is on the conveyer
                    if (in_array($belt[$workers[$i]->looksafter]->inventory, $workers[$i]->do_not_collect)) {
//                        array_push($worker_talk, [$workers[$i]->name . ' tries to pick up an item, its a finished product and he doesnt want it']);
                        continue 2;
                    }

                    // Check if item can be collected

                    if (!in_array($belt[$workers[$i]->looksafter]->inventory, $workers[$i]->collectable_items)) {
                        continue;
                    }

                    $workers[$i]->arms[$x] = $belt[$workers[$i]->looksafter]->inventory; // Pick up item from belt
                    $belt[$workers[$i]->looksafter]->inventory = $workers[$i]->define_blank_space; // Remove item from belt
                    $belt[$workers[$i]->looksafter]->status = 1; // Signal belt as busy
                    array_push($worker_talk, [$workers[$i]->name . ' has picked up ' . $workers[$i]->arms[$x]]);
                };


                // If has items needed to process
                $itemsToProcess = array_intersect($workers[$i]->items_to_process, $workers[$i]->arms);
                if ($itemsToProcess == $workers[$i]->items_to_process) {
                    $workers[$i]->increment++;

                    if ($workers[$i]->increment >= $workers[$i]->increment_time) {

                        // Reset Arms
                        for ($x = 0; $x < count($workers[$i]->arms); $x++) {
                            $workers[$i]->arms[$x] = false;
                        };

                        // Flag belt as busy
                        $belt[$workers[$i]->looksafter]->status = 1;

                        // Place processed item on belt
                        $belt[$workers[$i]->looksafter]->inventory = $workers[$i]->processed_item;

                        // Reset Increment
                        $workers[$i]->increment = 0;


                        array_push($worker_talk, [$workers[$i]->name . ' finished and placed product']);

                    }
                };
            }
        }

        return ['workers' => $workers, 'belt' => $belt, 'worker_talk' => $worker_talk];
    }


    /**
     * Moving Items
     *
     * @param $belt
     * @param $random
     * @param $i
     * @param $process_order
     * @return array
     */
    private function move($belt, $random, $i, $process_order = null)
    {
        // Move Belt - Belt will be limited to a maximum of 3 items

        // Increment Element
        for ($x = 0; $x < count($belt); $x++) {
            if (!empty($belt[$x])) {
                $belt[$x]->step = $belt[$x]->step + 1;
            }
        }

        // Check if we are handling unique, items, or if the user has pre-defined an order (for testing)
        if (!empty($process_order)) {
            $newinventory = $process_order[$i];
        } else {
            shuffle($random);
            $newinventory = $random[0];
        }

        // Handle creation of new object (Conveyer belt item)
        $newObject = (object) [
            'id' => $i,
            'inventory' => $newinventory, // Random Generation
            'status' => 0,
            'step' => 0,
        ];

        array_unshift($belt, $newObject);

        // Store the old item (Last conveyer belt item before deletion)
        $getDeletedItem = $belt[array_key_last($belt)];

        // Handle removal of old conveyer belt item
        array_pop($belt);

        // Return the new state of belt + the deleted item
        return [
            'belt' => $belt,
            'created' => $newObject->inventory,
            'removed' => $getDeletedItem,
        ];
    }
}
