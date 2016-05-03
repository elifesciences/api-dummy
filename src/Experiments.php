<?php

namespace eLife\Labs;

interface Experiments
{
    /**
     * @return Experiment[]
     */
    public function all() : array;

    /**
     * @throws ExperimentNotFound
     */
    public function get(int $number) : Experiment;
}
