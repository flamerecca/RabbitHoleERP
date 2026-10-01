<?php

namespace App\Services;

use FFI;
use FFI\CData;
use InvalidArgumentException;
use RuntimeException;

class NativeCosting
{
    /**
     * The native library version this wrapper was written against.
     */
    public const SUPPORTED_VERSION = 10000;

    /**
     * The C declarations exported by native/costing.
     */
    protected const DEFINITIONS = <<<'C'
        int rh_costing_version(void);
        double rh_avco_average_cost(double on_hand, double average_cost, double quantity, double unit_cost);
        int rh_fifo_consume(const double *remaining, const double *unit_costs, size_t layer_count, double quantity, double *consumed, double *total_cost);
        double rh_fifo_average_cost(const double *remaining, const double *unit_costs, size_t layer_count);
        C;

    protected FFI $ffi;

    /**
     * Load the native costing library and verify its version.
     *
     * @throws RuntimeException
     */
    public function __construct(?string $libraryPath = null)
    {
        $this->ffi = FFI::cdef(self::DEFINITIONS, $libraryPath ?? static::libraryPath());

        $version = $this->call('rh_costing_version');

        if ($version !== self::SUPPORTED_VERSION) {
            throw new RuntimeException("The native costing library version [{$version}] is not supported.");
        }
    }

    /**
     * Get the path of the compiled library for the current platform.
     *
     * @throws RuntimeException
     */
    public static function libraryPath(): string
    {
        $directory = base_path('native/costing/lib');

        return match (PHP_OS_FAMILY) {
            'Darwin' => "{$directory}/librh_costing.dylib",
            'Linux' => $directory.'/librh_costing-linux-'.(in_array(php_uname('m'), ['arm64', 'aarch64'], true) ? 'aarch64' : 'x86_64').'.so',
            default => throw new RuntimeException('The native costing library is not available on '.PHP_OS_FAMILY.'.'),
        };
    }

    /**
     * Get the moving average cost after receiving the given quantity at the given unit cost.
     */
    public function averageCost(float $onHand, float $averageCost, float $quantity, float $unitCost): float
    {
        return (float) $this->call('rh_avco_average_cost', $onHand, $averageCost, $quantity, $unitCost);
    }

    /**
     * Consume the quantity from the cost layers in the given order.
     *
     * @param  list<array{remaining: float, unit_cost: float}>  $layers
     * @return array{consumed: list<float>, total_cost: float}
     *
     * @throws InvalidArgumentException
     */
    public function consumeFifo(array $layers, float $quantity): array
    {
        [$remaining, $unitCosts] = $this->layerArrays($layers);
        $consumed = $this->doubles(count($layers));
        $totalCost = $this->doubles(1);

        $status = $this->call('rh_fifo_consume', $remaining, $unitCosts, count($layers), $quantity, $consumed, $totalCost);

        abort_if($status === -1, 409, __('The cost layers do not hold enough quantity.'));

        if ($status !== 0) {
            throw new InvalidArgumentException("The native costing library rejected the cost layers with status [{$status}].");
        }

        $taken = [];
        foreach (array_keys($layers) as $index) {
            $taken[] = (float) $consumed[$index];
        }

        return ['consumed' => $taken, 'total_cost' => (float) $totalCost[0]];
    }

    /**
     * Get the weighted average unit cost of the remaining quantity of the cost layers.
     *
     * @param  list<array{remaining: float, unit_cost: float}>  $layers
     */
    public function fifoAverageCost(array $layers): float
    {
        [$remaining, $unitCosts] = $this->layerArrays($layers);

        return (float) $this->call('rh_fifo_average_cost', $remaining, $unitCosts, count($layers));
    }

    /**
     * Copy the cost layers into two native double arrays.
     *
     * @param  list<array{remaining: float, unit_cost: float}>  $layers
     * @return array{0: CData, 1: CData}
     */
    protected function layerArrays(array $layers): array
    {
        $remaining = $this->doubles(count($layers));
        $unitCosts = $this->doubles(count($layers));

        foreach ($layers as $index => $layer) {
            $remaining[$index] = $layer['remaining'];
            $unitCosts[$index] = $layer['unit_cost'];
        }

        return [$remaining, $unitCosts];
    }

    /**
     * Allocate a zeroed native double array with room for at least one element.
     */
    protected function doubles(int $count): CData
    {
        return $this->ffi->new('double['.max(1, $count).']');
    }

    /**
     * Call an exported function of the native library.
     */
    protected function call(string $function, mixed ...$arguments): mixed
    {
        return $this->ffi->{$function}(...$arguments);
    }
}
