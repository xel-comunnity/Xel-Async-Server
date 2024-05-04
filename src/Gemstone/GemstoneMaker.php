<?php

namespace Xel\Async\Gemstone;

use InvalidArgumentException;
use Xel\Async\Contract\GemstoneMakerInterface;

class GemstoneMaker implements GemstoneMakerInterface
{
    private array $gemstones;

    public function __construct()
    {
        $this->initializeGemstones();
    }

    private function calculate_pickers_hardness($F, $d): float
    {
        $F = filter_var($F, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $d = filter_var($d, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if ($F === false || $d === false) {
            throw new InvalidArgumentException("Invalid input parameters.");
        }

        return 1.854 * ($F / pow($d, 2));
    }

    private function calculateHardnessValues($data): float|int
    {
        $hardness_values = [];
        foreach ($data['F_range'] as $F) {
            foreach ($data["d_range"] as $d) {
                $hardness_values[] = $this->calculate_pickers_hardness($F, $d);
            }
        }

        $average_hardness = array_sum($hardness_values) / count($hardness_values);
        return $average_hardness / $data["divisor"];
    }

    private function roundedValue($data): float
    {
        return round($this->calculateHardnessValues($data));
    }

    private function initializeGemstones(): void
    {
        $this->gemstones = [
            "Diamond" => $this->getGemstoneConfig("Diamond", 1000, 10000, 0.015, 0.030, 10),
            "Ruby" => $this->getGemstoneConfig("Ruby", 500, 3000, 0.020, 0.040, 9),
            "Sapphire" => $this->getGemstoneConfig("Sapphire", 1000, 2000, 0.020, 0.040, 9),
            "Lapis_Lazuli" => $this->getGemstoneConfig("Lapis_Lazuli", 200, 500, 0.030, 0.060, 5)
        ];
    }

    private function getGemstoneConfig($name, $fMin, $fMax, $dMin, $dMax, $divisor): array
    {
        $fStep = ($fMax - $fMin) / 4;
        $dStep = ($dMax - $dMin) / 4;

        return [
            "F_range" => $this->generateRandomRangeWithSteps($fMin, $fMax, 5, $fStep),
            "d_range" => $this->generateRandomRangeWithSteps($dMin, $dMax, 5, $dStep),
            "divisor" => $divisor
        ];
    }

    private function generateRandomRangeWithSteps($min, $max, $count, $step): array
    {
        $min = filter_var($min, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $max = filter_var($max, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $count = filter_var($count, FILTER_SANITIZE_NUMBER_INT);
        $step = filter_var($step, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if ($min === false 
        || $max === false 
        || $count === false 
        || $step === false 
        || $min >= $max 
        || $count <= 0 || $step <= 0) {
            throw new InvalidArgumentException("Invalid input parameters.");
        }

        $range = range($min, $max, $step);
        shuffle($range);
        return array_slice($range, 0, $count);
    }

    public function generateHashValues(): string
    {
        $roundedValues = [];

        foreach ($this->gemstones as $gemstone => $data) {
            $roundedValues[$gemstone] = $this->roundedValue($data);
        }

        $jsonData = json_encode(["rounded_values" => $roundedValues], JSON_PRETTY_PRINT);
        // Generate initial CSRF token
        return hash('sha256', $jsonData);
    }
}