<?php

namespace App\Http\Controllers;

use App\Library\Services\ConveyorBelt;
use App\Library\Services\FormattedView;
use Illuminate\Http\Request;

class ConveyorBeltController extends Controller
{
    private ConveyorBelt $conveyorBelt;
    private FormattedView $formattedView;

    /**
     * @param ConveyorBelt $conveyorBelt
     * @param FormattedView $formattedView
     */
    public function __construct(ConveyorBelt $conveyorBelt, FormattedView $formattedView)
    {
        $this->conveyorBelt = $conveyorBelt;
        $this->formattedView = $formattedView;
    }

    /**
     * Index function where all processing will be done to make conveyor calculations
     *
     * @return void
     */
    public function index()
    {
        $this->formattedView->setAnswer($this->conveyorBelt->runConveyor());
        $process = $this->formattedView->create();

        return view('welcome', [
            'processed' => $process['total_finished'],
            'empty' => $process['results']['EMPTY'],
            'steps' => $process['steps'],
        ]);

    }
}
