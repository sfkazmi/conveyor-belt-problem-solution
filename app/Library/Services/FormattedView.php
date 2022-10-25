<?php

namespace App\Library\Services;

use App\Library\Helpers\Helpers;

class FormattedView
{
    private $answer;
    private $total;

    /**
     * @return array
     */
    public function create()
    {
        // Create buld Inventory
        $build_inventory = [];
        for ($i = 0; $i < count($this->answer->return_all); $i++) {


            // Remove all inventory items that may return null
            if (!isset($this->answer->return_all[$i]->inventory)) {
                continue;
            }

            // Push all inventory items to a single array
            if ($this->answer->return_all[$i]->inventory) {
                array_push($build_inventory, $this->answer->return_all[$i]->inventory);
            }
        }

        // Filter our similar items in to groups
        $results = array_count_values($build_inventory);

        $steps = [];
        for ($i = 0; $i < count($this->answer->log); $i++) {
            for ($x = 0; $x < count($this->answer->log[$i]); $x++) {
                for ($z = 0; $z < count($this->answer->log[$i][$x]); $z++) {
                    array_push($steps, $this->answer->log[$i][$x][$z]);
                }
            }
        }

        return [
            'results' => $results,
            'total_finished' => $this->total,
            'steps' => $steps,
        ];
    }

    /**
     * Set final results
     *
     * @param $answer
     * @return void
     */
    public function setAnswer($answer)
    {
        $this->total = Helpers::getProcessedItemCount(Helpers::array_flatten($answer->log));
        $this->answer = $answer;
    }


}
