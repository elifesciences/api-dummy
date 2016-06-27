<?php

namespace eLife\Api;

use InvalidArgumentException;

final class InMemoryExperiments implements Experiments
{
    private $experiments = [];

    /**
     * @var Experiments $experiments
     */
    public function __construct(array $experiments)
    {
        foreach ($experiments as $experiment) {
            $this->add($experiment);
        }
    }

    public function add(Experiment $experiment)
    {
        $next = count($this->experiments) + 1;

        if ($experiment->getNumber() !== $next) {
            throw new InvalidArgumentException('Expected experiment ' . $next);
        }

        $this->experiments[$experiment->getNumber()] = $experiment;
    }

    /**
     * @return Experiment[]
     */
    public function all() : array
    {
        return array_reverse($this->experiments);
    }

    /**
     * @throws ExperimentNotFound
     */
    public function get(int $number) : Experiment
    {
        if (false === isset($this->experiments[$number])) {
            throw ExperimentNotFound::fromNumber($number);
        }

        return $this->experiments[$number];
    }
}
